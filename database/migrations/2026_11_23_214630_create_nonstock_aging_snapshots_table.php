<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nonstock_aging_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->date('snapshot_date')->index();
            $table->string('scope', 20)->default('all');
            $table->string('mrp_scope', 20)->default('NONSTOCK');
            $table->unsignedInteger('total_outstanding_items')->default(0);
            $table->unsignedInteger('total_outstanding_po')->default(0);
            $table->decimal('total_outstanding_value', 18, 2)->default(0);
            $table->unsignedInteger('on_check_items')->default(0);
            $table->decimal('on_check_value', 18, 2)->default(0);
            $table->unsignedInteger('normal_count')->default(0);
            $table->decimal('normal_value', 18, 2)->default(0);
            $table->unsignedInteger('attention_count')->default(0);
            $table->decimal('attention_value', 18, 2)->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->decimal('warning_value', 18, 2)->default(0);
            $table->unsignedInteger('critical_count')->default(0);
            $table->decimal('critical_value', 18, 2)->default(0);
            $table->unsignedInteger('scheduled_hold_count')->default(0);
            $table->decimal('scheduled_hold_value', 18, 2)->default(0);
            $table->unsignedInteger('unattended_critical_count')->default(0);
            $table->decimal('unattended_critical_value', 18, 2)->default(0);
            $table->decimal('pic_mapped_percentage', 5, 2)->default(0);
            $table->decimal('warning_critical_followed_up_percentage', 5, 2)->default(0);
            $table->json('unit_breakdown')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nonstock_aging_snapshots');
    }
};
