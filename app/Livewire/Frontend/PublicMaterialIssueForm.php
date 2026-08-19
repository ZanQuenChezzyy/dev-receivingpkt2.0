<?php

namespace App\Livewire\Frontend;

use App\Models\DeliveryOrderReceiptDetail;
use App\Models\MaterialIssue;
use App\Models\MaterialIssueDetail;
use App\Models\PurchaseOrderIssued;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class PublicMaterialIssueForm extends Component
{
    // Form Properties
    public string $diminta_oleh = '';

    public string $npk = '';

    public string $diterima_oleh = '';

    public string $no_hp = '';

    public string $departemen = '';

    public string $bagian = '';

    // Signatures
    public ?string $diminta_signature = null;

    public string $disetujui_oleh = '';

    public string $disetujui_npk = '';

    public ?string $disetujui_signature = null;

    public bool $requiresIstekSignature = false;

    public string $diserahkan_oleh = '';

    public string $diserahkan_npk = '';

    public ?string $diserahkan_signature = null;

    public string $tanggal = '';

    public string|int $purchase_order_issued_id = '';

    public string $no_reservasi = '';

    public string $no_jor_wo = '';

    public string $no_alat = '';

    public string $kode_biaya = '';

    public string $digunakan_untuk = '';

    public bool $agreement = false;

    public array $details = [];

    // Custom Options
    public string $pilihan_istek = '';

    public string $pilihan_receiving = '';

    public array $receiving_users = [];

    // Search properties
    #[Url(as: 'po')]
    public string $po_search = '';
    public string $search_mode = 'po'; // 'po' or 'sn'

    public mixed $available_pos = [];

    public array $available_po_items = [];

    public bool $showSuccessMessage = false;

    public bool $showConfirmModal = false;

    public function mount()
    {
        $this->tanggal = now()->format('Y-m-d');
        $this->addDetail();
        $this->searchPOs();

        $this->receiving_users = \App\Models\User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'AVP Receiving');
        })->get(['id', 'name', 'npk'])->toArray();

        if (!empty($this->po_search) && count($this->available_pos) > 0) {
            $matchedPo = collect($this->available_pos)->firstWhere('purchase_order_no', $this->po_search);
            if ($matchedPo) {
                $this->purchase_order_issued_id = $matchedPo->id;
                $this->updatedPurchaseOrderIssuedId($this->purchase_order_issued_id);
            }
        }
    }

    public function updatedDimintaOleh(string $value)
    {
        $this->diterima_oleh = $value;
    }

    public function updatedPilihanIstek(string $value)
    {
        if ($value === 'Pasarela') {
            $this->disetujui_oleh = 'Pasarela';
            $this->disetujui_npk = '4124213';
        } elseif ($value === 'Joko') {
            $this->disetujui_oleh = 'Joko';
            $this->disetujui_npk = 'KNEB22684';
        } else {
            $this->disetujui_oleh = '';
            $this->disetujui_npk = '';
        }
    }

    public function updatedPilihanReceiving(string $value)
    {
        if ($value && $value !== 'Lainnya') {
            $user = collect($this->receiving_users)->firstWhere('id', (int) $value);
            if ($user) {
                $this->diserahkan_oleh = $user['name'];
                $this->diserahkan_npk = $user['npk'] ?? '';
            }
        } else {
            $this->diserahkan_oleh = '';
            $this->diserahkan_npk = '';
        }
    }

    public function updatedPoSearch()
    {
        $this->searchPOs();
    }

    public function updatedSearchMode()
    {
        $this->po_search = '';
        $this->available_pos = [];
    }

    public function searchPOs()
    {
        $query = PurchaseOrderIssued::whereHas('deliveryOrderReceiptDetails');

        if (!empty($this->po_search)) {
            if ($this->search_mode === 'po') {
                $query->where('purchase_order_no', 'like', '%' . $this->po_search . '%');
            } else {
                $query->whereHas('deliveryOrderReceiptDetails', function ($q2) {
                    $q2->where('material_code', 'like', '%' . $this->po_search . '%');
                });
            }
        }

        $this->available_pos = $query->limit(20)->get()->unique('purchase_order_no');
    }

    public function updatedPurchaseOrderIssuedId(mixed $id)
    {
        if ($id) {
            $poItem = PurchaseOrderIssued::find($id);
            if ($poItem) {
                $allPoItemIds = PurchaseOrderIssued::where('purchase_order_no', $poItem->purchase_order_no)->pluck('id');
                $rawItems = DeliveryOrderReceiptDetail::with([
                    'locationReceiving',
                    'deliveryOrderReceipt.grsRdtvItems.grsRdtv'
                ])
                    ->whereIn('purchase_order_issued_id', $allPoItemIds)
                    ->get();

                $this->available_po_items = $rawItems->groupBy('item_no')->map(function ($group) {
                    $first = $group->first();

                    $combined_quantity = $group->sum('quantity');
                    $combined_issued = $group->sum(function ($item) {
                        return $item->issued_quantity;
                    });
                    $combined_boh = $combined_quantity - $combined_issued;

                    $locations = $group->map(fn($i) => $i->locationReceiving?->name)->filter()->unique()->implode(', ');

                    $has_non_grs = $group->contains(function ($item) {
                        if (!$item->deliveryOrderReceipt) {
                            return true;
                        }

                        $has_grs_category = $item->deliveryOrderReceipt->grsRdtvItems->contains(function ($grsItem) {
                            return $grsItem->grsRdtv && $grsItem->grsRdtv->category === 'GRS';
                        });

                        return !$has_grs_category;
                    });

                    return [
                        'id' => $first->item_no, // Gunakan item_no sebagai ID di frontend
                        'item_no' => $first->item_no,
                        'material_code' => $first->material_code,
                        'description' => $first->description,
                        'uoi' => $first->uoi,
                        'combined_boh' => $combined_boh,
                        'combined_locations' => $locations ?: 'Belum Diatur',
                        'has_non_grs' => $has_non_grs,
                    ];
                })->sortBy('item_no')->values()->toArray();
            } else {
                $this->available_po_items = [];
            }
        } else {
            $this->available_po_items = [];
        }

        // Reset details
        $this->details = [];
        $this->addDetail();
    }

    public function updatedDetails(mixed $value, string $key)
    {
        // $key looks like "0.delivery_order_receipt_detail_id" or "0.diminta"
        $parts = explode('.', $key);
        if (count($parts) === 2) {
            $index = $parts[0];
            $field = $parts[1];

            if ($field === 'delivery_order_receipt_detail_id') {
                $detailId = $value;
                if ($detailId) {
                    $item = collect($this->available_po_items)->firstWhere('id', (int) $detailId)
                        ?? collect($this->available_po_items)->firstWhere('id', (string) $detailId);

                    if ($item) {
                        $this->details[$index]['stock_no'] = $item['material_code'];
                        $this->details[$index]['description'] = $item['description'];
                        $this->details[$index]['location'] = $item['combined_locations'];
                        $this->details[$index]['uoi'] = $item['uoi'];
                        $this->details[$index]['boh'] = $item['combined_boh'];
                    }
                } else {
                    $this->details[$index]['stock_no'] = '';
                    $this->details[$index]['description'] = '';
                    $this->details[$index]['location'] = '';
                    $this->details[$index]['uoi'] = '';
                    $this->details[$index]['boh'] = 0;
                }
            } elseif ($field === 'diminta') {
                // Auto fill diserahkan
                $this->details[$index]['diserahkan'] = $value;
            }
        }

        $this->checkIfIstekSignatureRequired();
    }

    public function addDetail($showToast = false)
    {
        if (count($this->details) < count($this->available_po_items) || empty($this->available_po_items)) {
            $this->details[] = [
                'delivery_order_receipt_detail_id' => '',
                'stock_no' => '',
                'description' => '',
                'location' => '',
                'uoi' => '',
                'diminta' => '',
                'diserahkan' => '',
                'boh' => 0,
            ];

            if ($showToast) {
                $this->dispatch('item-added');
            }
        }
    }

    public function removeDetail(int $index)
    {
        if (count($this->details) > 1) {
            unset($this->details[$index]);
            $this->details = array_values($this->details);
            $this->checkIfIstekSignatureRequired();
            $this->dispatch('item-removed');
        }
    }

    protected function checkIfIstekSignatureRequired()
    {
        $requires = false;
        foreach ($this->details as $detail) {
            $detailId = $detail['delivery_order_receipt_detail_id'] ?? null;
            if ($detailId) {
                $item = collect($this->available_po_items)->firstWhere('id', (int) $detailId)
                    ?? collect($this->available_po_items)->firstWhere('id', (string) $detailId);

                if ($item && !empty($item['has_non_grs'])) {
                    $requires = true;
                    break;
                }
            }
        }
        $this->requiresIstekSignature = $requires;
    }

    public function rules()
    {
        $rules = [
            'diminta_oleh' => 'required|string',
            'npk' => 'required|string',
            'diterima_oleh' => 'required|string',
            'no_hp' => 'required|string',
            'departemen' => 'required|string',
            'bagian' => 'required|string',
            'tanggal' => 'required|date',
            'purchase_order_issued_id' => 'required',
            'digunakan_untuk' => 'required|string',
            'agreement' => 'accepted',
            'diminta_signature' => 'required|string',
            'diserahkan_oleh' => 'required|string',
            'diserahkan_npk' => 'required|string',
            'diserahkan_signature' => 'required|string',
            'details.*.delivery_order_receipt_detail_id' => ['required', 'distinct'],
            'details.*.diminta' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    // Extract index
                    $parts = explode('.', $attribute);
                    $index = $parts[1];
                    $boh = (float) ($this->details[$index]['boh'] ?? 0);
                    if ((float) $value > $boh) {
                        $fail("Kuantitas diminta melebihi sisa stok (BOH: {$boh}).");
                    }
                },
            ],
            'details.*.diserahkan' => 'required|numeric',
        ];

        if ($this->requiresIstekSignature) {
            $rules['disetujui_oleh'] = 'required|string';
            $rules['disetujui_npk'] = 'required|string';
            $rules['disetujui_signature'] = 'required|string';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'purchase_order_issued_id.required' => 'PO wajib dipilih.',
            'details.*.delivery_order_receipt_detail_id.required' => 'Material wajib dipilih.',
            'details.*.diminta.required' => 'Qty wajib diisi.',
            'details.*.diminta.numeric' => 'Qty harus berupa angka.',
            'details.*.diminta.min' => 'Qty harus lebih dari 0.',
            'agreement.accepted' => 'Anda harus menyetujui pernyataan ini sebelum mengirim form.',
            'diminta_signature.required' => 'Tanda tangan peminta wajib diisi.',
            'disetujui_oleh.required' => 'Nama ISTEK wajib diisi karena ada barang yang belum GRS.',
            'disetujui_npk.required' => 'NPK ISTEK wajib diisi.',
            'disetujui_signature.required' => 'Tanda tangan ISTEK wajib diisi karena ada barang yang belum GRS.',
            'diserahkan_oleh.required' => 'Nama pihak Receiving wajib diisi.',
            'diserahkan_npk.required' => 'NPK pihak Receiving wajib diisi.',
            'diserahkan_signature.required' => 'Tanda tangan pihak Receiving wajib diisi.',
        ];
    }

    public function confirmSubmit()
    {
        $this->diterima_oleh = $this->diminta_oleh;
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('validation-failed');
            throw $e;
        }
        $this->showConfirmModal = true;
    }

    public function submit()
    {
        $this->diterima_oleh = $this->diminta_oleh;
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('validation-failed');
            throw $e;
        }

        DB::beginTransaction();
        try {
            $mirNumber = 'MIR-' . date('Ymd') . '-' . Str::upper(Str::random(4));

            $issue = MaterialIssue::create([
                'mir_number' => $mirNumber,
                'tanggal' => $this->tanggal,
                'purchase_order_issued_id' => $this->purchase_order_issued_id,
                'no_hp' => $this->no_hp,
                'no_reservasi' => $this->no_reservasi,
                'departemen' => $this->departemen,
                'bagian' => $this->bagian,
                'no_jor_wo' => $this->no_jor_wo,
                'digunakan_untuk' => $this->digunakan_untuk,
                'no_alat' => $this->no_alat,
                'kode_biaya' => $this->kode_biaya,
                'diminta_oleh' => $this->diminta_oleh,
                'npk' => $this->npk,
                'diminta_signature' => $this->diminta_signature,
                'diterima_oleh' => $this->diterima_oleh,
                'disetujui_oleh' => $this->requiresIstekSignature ? $this->disetujui_oleh : null,
                'disetujui_npk' => $this->requiresIstekSignature ? $this->disetujui_npk : null,
                'disetujui_signature' => $this->requiresIstekSignature ? $this->disetujui_signature : null,
                'diserahkan_oleh' => $this->diserahkan_oleh,
                'diserahkan_npk' => $this->diserahkan_npk,
                'diserahkan_signature' => $this->diserahkan_signature,
                'created_by' => null,
            ]);

            $parentPoItem = PurchaseOrderIssued::find($this->purchase_order_issued_id);
            $poNo = $parentPoItem ? $parentPoItem->purchase_order_no : null;
            $allPoItemIds = $poNo ? PurchaseOrderIssued::where('purchase_order_no', $poNo)->pluck('id') : collect([]);

            foreach ($this->details as $detailData) {
                $itemNo = $detailData['delivery_order_receipt_detail_id']; // ini berisi item_no sekarang
                $diminta = (float) $detailData['diminta'];

                // Ambil semua DO receipts untuk PO item ini, prioritaskan yang BOH-nya lebih dari 0
                $receipts = DeliveryOrderReceiptDetail::whereIn('purchase_order_issued_id', $allPoItemIds)
                    ->where('item_no', $itemNo)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($receipts as $receipt) {
                    if ($diminta <= 0) {
                        break;
                    }

                    $receiptBoh = (float) $receipt->quantity - (float) $receipt->issued_quantity;
                    if ($receiptBoh <= 0) {
                        continue;
                    }

                    $take = min($receiptBoh, $diminta);

                    MaterialIssueDetail::create([
                        'material_issue_id' => $issue->id,
                        'delivery_order_receipt_detail_id' => $receipt->id,
                        'diminta' => $take,
                        'diserahkan' => $take, // Default diserahkan = diminta
                        'boh' => $receiptBoh,
                    ]);

                    $diminta -= $take;
                }
            }

            DB::commit();

            // Reset specific fields
            $this->diterima_oleh = '';
            $this->purchase_order_issued_id = '';
            $this->po_search = '';
            $this->no_reservasi = '';
            $this->no_jor_wo = '';
            $this->no_alat = '';
            $this->kode_biaya = '';
            $this->digunakan_untuk = '';
            $this->diminta_signature = null;
            $this->pilihan_istek = '';
            $this->disetujui_oleh = '';
            $this->disetujui_npk = '';
            $this->disetujui_signature = null;
            $this->pilihan_receiving = '';
            $this->diserahkan_oleh = '';
            $this->diserahkan_npk = '';
            $this->diserahkan_signature = null;
            $this->agreement = false;
            $this->details = [];
            $this->addDetail();

            $this->showConfirmModal = false;
            $this->showSuccessMessage = true;
            $this->dispatch('mir-submitted');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('submit', 'Gagal menyimpan pengajuan: ' . $e->getMessage());
        }
    }

    #[Layout('components.layouts.frontend')]
    public function render()
    {
        return view('livewire.frontend.public-material-issue-form');
    }
}
