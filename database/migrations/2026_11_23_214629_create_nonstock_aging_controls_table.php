<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nonstock_aging_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_detail_id')
                ->nullable()
                ->unique()
                ->constrained('delivery_order_receipt_details')
                ->nullOnDelete();
            $table->foreignId('purchase_order_issued_id')
                ->nullable()
                ->constrained('purchase_order_issueds')
                ->nullOnDelete();
            $table->string('po_by_item', 30)->nullable()->index();
            $table->string('data_source', 20)->default('web_do')->index();

            // Snapshot metadata untuk item impor historis tanpa DO Web
            $table->string('mrp_type_snapshot', 30)->nullable()->index();
            $table->string('material_type_snapshot', 20)->nullable();
            $table->string('material_code_snapshot', 40)->nullable();
            $table->text('description_snapshot')->nullable();
            $table->string('vendor_name_snapshot')->nullable();
            $table->string('uoi_snapshot', 20)->nullable();
            $table->string('requisitioner_raw')->nullable();

            // T0 & nilai lokal (IDR) untuk perhitungan aging
            $table->date('grs_posting_date')->nullable()->index();
            $table->decimal('initial_qty_gr', 15, 2)->nullable();
            $table->decimal('manual_qty_issued', 15, 2)->default(0);
            $table->decimal('initial_value_lc', 18, 2)->nullable();
            $table->string('location_text', 120)->nullable();

            // Pemetaan unit peminta & override PIC per item
            $table->foreignId('requesting_unit_id')
                ->nullable()
                ->constrained('requesting_units')
                ->nullOnDelete();
            $table->string('pic_name_override')->nullable();
            $table->string('pic_email_override')->nullable();
            $table->string('pic_phone_override', 40)->nullable();

            // Status operasional, cek fisik, dan tindak lanjut
            $table->string('operational_status', 30)->default('Belum Diambil')->index();
            $table->string('followup_stage', 40)->default('Belum Ditindaklanjuti')->index();
            $table->boolean('physical_checked')->default(false)->index();
            $table->timestamp('warehouse_validated_at')->nullable();
            $table->foreignId('warehouse_validated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('warehouse_validation_notes')->nullable();

            // Komitmen ambil / Scheduled Hold / Pemindahan gudang
            $table->date('planned_pickup_date')->nullable()->index();
            $table->text('scheduled_hold_reason')->nullable();
            $table->string('delay_reason_category', 80)->nullable();
            $table->string('moved_to_location', 120)->nullable();
            $table->timestamp('moved_at')->nullable();

            // Ringkasan reminder & eskalasi 2 tingkat (AVP & VP)
            $table->timestamp('last_reminded_at')->nullable();
            $table->unsignedInteger('reminder_count')->default(0);
            $table->timestamp('avp_escalated_at')->nullable();
            $table->timestamp('vp_escalated_at')->nullable();
            $table->text('last_followup_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nonstock_aging_controls');
    }
};
