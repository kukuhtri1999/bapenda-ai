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
        Schema::create('peserta_lotre', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->nullable();
            $table->string('nopol')->nullable();
            $table->boolean('apakah_menang')->default(false);
            $table->integer('urutan_menang')->nullable()->index();
            $table->timestamps();

            $table->index('apakah_menang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_lotre');
    }
};
