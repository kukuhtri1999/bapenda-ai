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
        Schema::table('peserta_lotre', function (Blueprint $table) {
            $table->index('nopol', 'peserta_lotre_nopol_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peserta_lotre', function (Blueprint $table) {
            $table->dropIndex('peserta_lotre_nopol_index');
        });
    }
};
