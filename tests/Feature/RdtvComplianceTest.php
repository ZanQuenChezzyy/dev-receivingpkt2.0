<?php

use App\Models\DeliveryOrderReceipt;
use App\Models\QcHistory;
use App\Models\RdtvCompliance;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('users')) {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('vendors')) {
        Schema::create('vendors', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('vendor_number')->nullable();
            $table->text('email')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipts')) {
        Schema::create('delivery_order_receipts', function ($table) {
            $table->id();
            $table->string('delivery_order_no')->nullable();
            $table->string('document_code')->nullable();
            $table->date('received_date')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('post_103')->nullable();
            $table->string('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('receipt_mode')->default('Standard');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('delivery_order_receipt_delay_logs')) {
        Schema::create('delivery_order_receipt_delay_logs', function ($table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->string('delay_reason')->nullable();
            $table->text('delay_notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('qc_histories')) {
        Schema::create('qc_histories', function ($table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('rdtv_compliances')) {
        Schema::create('rdtv_compliances', function ($table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_receipt_id')->nullable();
            $table->unsignedBigInteger('purchase_order_issued_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('document_type')->default('COO / Sertifikat');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('vendor_notes')->nullable();
            $table->string('status')->default('Menunggu Review');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }
});

test('rdtv compliance model has working relations and helper methods', function () {
    $user = User::create(['name' => 'QC Reviewer', 'email' => 'qc@example.com', 'password' => 'secret']);
    $vendor = Vendor::create(['name' => 'PT Flow Tech', 'vendor_number' => '1001', 'email' => 'vendor@example.com']);
    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-999',
        'status' => 'RDTV',
        'delay_reason' => 'RDTV',
        'delay_notes' => 'TIDAK MELAMPIRKAN COO FROM MANUFACTURE',
    ]);

    $compliance = RdtvCompliance::create([
        'delivery_order_receipt_id' => $do->id,
        'vendor_id' => $vendor->id,
        'document_type' => 'COO / Sertifikat',
        'file_path' => 'rdtv-compliances/5300065382/coo.pdf',
        'original_filename' => 'Certificate_Of_Origin.pdf',
        'file_size' => 102400,
        'vendor_notes' => 'COO resmi dari manufacturer.',
        'status' => 'Menunggu Review',
    ]);

    expect($compliance->isPending())->toBeTrue()
        ->and($compliance->isApproved())->toBeFalse()
        ->and($compliance->isRejected())->toBeFalse()
        ->and($compliance->deliveryOrderReceipt->id)->toBe($do->id)
        ->and($compliance->vendor->id)->toBe($vendor->id)
        ->and($do->rdtvCompliances->count())->toBe(1);

    // Review and approve
    $compliance->update([
        'status' => 'Disetujui',
        'reviewed_by' => $user->id,
        'reviewed_at' => now(),
        'review_notes' => 'COO terverifikasi valid.',
    ]);

    expect($compliance->fresh()->isApproved())->toBeTrue()
        ->and($compliance->fresh()->reviewedBy->id)->toBe($user->id);
});

test('approving rdtv compliance returns do to ready status and records delay log', function () {
    $user = User::create(['name' => 'Petugas Receiving', 'email' => 'receiving@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $do = DeliveryOrderReceipt::create([
        'delivery_order_no' => 'DO-12345',
        'status' => 'RDTV',
        'delay_reason' => 'RDTV',
        'delay_notes' => 'TIDAK MELAMPIRKAN COO FROM MANUFACTURE',
    ]);

    QcHistory::create([
        'delivery_order_receipt_id' => $do->id,
        'status' => 'Kembali',
    ]);

    $compliance = RdtvCompliance::create([
        'delivery_order_receipt_id' => $do->id,
        'original_filename' => 'COO_Manufacture.pdf',
        'file_path' => 'rdtv-compliances/coo.pdf',
        'status' => 'Menunggu Review',
    ]);

    // Approve the compliance
    $compliance->update([
        'status' => 'Disetujui',
        'reviewed_by' => $user->id,
        'reviewed_at' => now(),
        'review_notes' => 'Dokumen lengkap dan absah.',
    ]);

    $latestQc = $do->qcHistories()->latest()->first();
    $do->status = ($latestQc && $latestQc->status === 'Kembali') ? 'Kembali dari ISTEK' : 'Diterima';
    $do->delay_reason = null;
    $do->delay_notes = null;
    $do->save();

    $do->delayLogs()->create([
        'delay_reason' => 'Dokumen Pemenuhan Disetujui',
        'delay_notes' => 'Sertifikat susulan '.$compliance->original_filename.' disetujui oleh '.$user->name.'. DO siap diproses untuk penerbitan GRS.',
        'created_by' => $user->id,
    ]);

    expect($do->fresh()->status)->toBe('Kembali dari ISTEK')
        ->and($do->fresh()->delay_reason)->toBeNull()
        ->and($do->delayLogs()->where('delay_reason', 'Dokumen Pemenuhan Disetujui')->exists())->toBeTrue();
});
