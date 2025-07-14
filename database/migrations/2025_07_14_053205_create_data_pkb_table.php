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
        Schema::create('data_pkb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_wajib_pajak')->nullable()->constrained('wajib_pajak')->onDelete('set null');
            $table->string('nopol')->nullable();
            $table->string('warna')->nullable();
            $table->string('model')->nullable();
            $table->string('merk')->nullable();
            $table->string('type')->nullable();
            $table->year('tahun')->nullable();
            $table->date('tanggal_masa_pajak')->nullable();
            $table->decimal('pkb', 15, 2)->nullable();
            $table->decimal('opsen_pkb', 15, 2)->nullable();
            $table->decimal('pkb_progresif', 15, 2)->nullable();
            $table->decimal('opsen_pkb_prog', 15, 2)->nullable();
            $table->decimal('swdkllj', 15, 2)->nullable();
            $table->decimal('parkir_berlangganan', 15, 2)->nullable();
            $table->decimal('pengesahan_stnk', 15, 2)->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_pkb');
    }
};
