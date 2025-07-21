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
        Schema::table('wajib_pajak', function (Blueprint $table) {
            $table->dropColumn('lima_digit_terakhir_no_rangka');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wajib_pajak', function (Blueprint $table) {
            $table->string('lima_digit_terakhir_no_rangka', 5)->nullable();
        });
    }
};
