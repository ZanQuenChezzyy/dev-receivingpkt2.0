<?php

namespace App\Models;

use App\Enums\AgingFollowupStage;
use App\Enums\AgingOperationalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class MaterialIssueDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_issue_id',
        'delivery_order_receipt_detail_id',
        'diminta',
        'diserahkan',
        'boh',
        'stage_when_issued',
    ];

    protected $casts = [
        'diminta' => 'decimal:2',
        'diserahkan' => 'decimal:2',
    ];

    public function materialIssue()
    {
        return $this->belongsTo(MaterialIssue::class);
    }

    public function deliveryOrderReceiptDetail()
    {
        return $this->belongsTo(DeliveryOrderReceiptDetail::class);
    }

    protected static function booted()
    {
        static::saving(function ($detail) {
            if (empty($detail->stage_when_issued) && $detail->delivery_order_receipt_detail_id) {
                $doReceiptDetail = DeliveryOrderReceiptDetail::with('deliveryOrderReceipt')->find($detail->delivery_order_receipt_detail_id);
                if ($doReceiptDetail && $doReceiptDetail->deliveryOrderReceipt) {
                    $detail->stage_when_issued = $doReceiptDetail->deliveryOrderReceipt->stage ?? 'ON_RECEIPT';
                }
            }
        });

        static::saved(function (MaterialIssueDetail $detail): void {
            if (! $detail->delivery_order_receipt_detail_id) {
                return;
            }

            if (! Schema::hasTable('nonstock_aging_controls')) {
                return;
            }

            $control = NonStockAgingControl::query()
                ->with(['deliveryOrderReceiptDetail.deliveryOrderReceipt.grsRdtvItems.grsRdtv', 'deliveryOrderReceiptDetail.materialIssueDetails'])
                ->where('delivery_order_receipt_detail_id', $detail->delivery_order_receipt_detail_id)
                ->first();

            if ($control === null || $control->operational_status === AgingOperationalStatus::SelesaiDiambil) {
                return;
            }

            $receivedQty = $control->resolvedReceivedQuantity();
            $issuedQty = (float) self::query()
                ->where('delivery_order_receipt_detail_id', $detail->delivery_order_receipt_detail_id)
                ->sum('diserahkan');

            if ($receivedQty > 0 && $issuedQty >= $receivedQty) {
                $control->operational_status = AgingOperationalStatus::SelesaiDiambil;
                $control->followup_stage = AgingFollowupStage::Selesai;
                $control->manual_qty_issued = max((float) $control->manual_qty_issued, $issuedQty);
                $control->save();

                if (Schema::hasTable('nonstock_aging_logs')) {
                    NonStockAgingLog::create([
                        'nonstock_aging_control_id' => $control->id,
                        'action_type' => 'auto_closed_mir',
                        'aging_days_snapshot' => $control->resolvedAgingDays(),
                        'aging_zone_snapshot' => $control->resolvedAgingZone()->value,
                        'outstanding_value_snapshot' => 0,
                        'channel' => 'Sistem',
                        'recipient_level' => 'Internal',
                        'notes' => 'Otomatis ditutup setelah pencatatan MIR memenuhi kuantitas penerimaan.',
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        });
    }
}
