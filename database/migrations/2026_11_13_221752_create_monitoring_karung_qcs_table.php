<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitoring_karung_qcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitoring_karung_id')->constrained('monitoring_karungs')->cascadeOnDelete();
            $table->string('tahap_qc', 50);
            $table->unsignedBigInteger('qty_qc')->default(0);
            $table->string('no_dof_daan', 100)->nullable();
            $table->date('tgl_permintaan')->nullable();
            $table->string('no_sik', 100)->nullable();
            $table->date('tgl_sik')->nullable();
            $table->string('no_surat_qc', 100)->nullable();
            $table->string('status_qc', 50)->default('Diterima');
            $table->string('file_istek', 100)->nullable();
            $table->string('no_bapp', 100)->nullable();
            $table->string('no_sa', 50)->nullable();
            $table->string('drafter', 100)->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('no_sik');
            $table->index('no_sa');
            $table->index('status_qc');
            $table->index('tgl_sik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_karung_qcs');
    }
};
