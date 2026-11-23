<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nonstock_aging_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nonstock_aging_control_id')
                ->constrained('nonstock_aging_controls')
                ->cascadeOnDelete();
            $table->string('action_type', 40)->index();
            $table->integer('aging_days_snapshot')->default(0);
            $table->string('aging_zone_snapshot', 20)->default('Normal');
            $table->decimal('outstanding_value_snapshot', 18, 2)->default(0);
            $table->string('channel', 40)->default('Sistem');
            $table->string('recipient_level', 20)->default('Internal');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_contact')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nonstock_aging_logs');
    }
};
