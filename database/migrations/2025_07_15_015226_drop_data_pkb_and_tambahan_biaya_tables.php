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
        Schema::dropIfExists('tambahan_biaya');
        Schema::dropIfExists('data_pkb');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate data_pkb table
        Schema::create('data_pkb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_wajib_pajak')->constrained('wajib_pajak');
            $table->string('nopol');
            $table->string('warna')->nullable();
            $table->string('merk')->nullable();
            $table->string('model')->nullable();
            $table->string('type')->nullable();
            $table->string('tahun')->nullable();
            $table->date('tanggal_masa_pajak')->nullable();
            $table->decimal('pkb', 15, 2)->default(0);
            $table->decimal('opsen_pkb', 15, 2)->default(0);
            $table->decimal('pkb_progresif', 15, 2)->default(0);
            $table->decimal('opsen_pkb_prog', 15, 2)->default(0);
            $table->decimal('swdkllj', 15, 2)->default(0);
            $table->decimal('parkir_berlangganan', 15, 2)->default(0);
            $table->decimal('pengesahan_stnk', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Recreate tambahan_biaya table
        Schema::create('tambahan_biaya', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_data_pkb')->constrained('data_pkb');
            $table->string('label_biaya');
            $table->decimal('harga_biaya', 15, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
