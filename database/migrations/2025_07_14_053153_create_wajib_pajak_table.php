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
        Schema::create('wajib_pajak', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nopol'); // Format: AA 0000 ZZZ
            $table->string('lima_digit_terakhir_no_rangka', 5);
            $table->string('nomer_wa');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wajib_pajak');
    }
};
