<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoicing_email_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoicing_receipt_id')->nullable()->constrained('invoicing_receipts')->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jenis', 16)->default('GRS');
            $table->string('trigger_source', 32)->default('batch_ocr');
            $table->json('recipients')->nullable();
            $table->json('original_recipients')->nullable();
            $table->json('cc_recipients')->nullable();
            $table->string('subject');
            $table->longText('body_html')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status', 20)->default('skipped')->index();
            $table->string('zimbra_message_id', 120)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['invoicing_receipt_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoicing_email_logs');
    }
};
