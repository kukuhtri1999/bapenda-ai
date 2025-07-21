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
        // Drop captcha table if exists
        Schema::dropIfExists('captcha');

        // Make sure wajib_pajak table only has what we need
        if (Schema::hasTable('wajib_pajak')) {
            // Drop columns we don't need if they exist
            Schema::table('wajib_pajak', function (Blueprint $table) {
                if (Schema::hasColumn('wajib_pajak', 'lima_digit_terakhir_no_rangka')) {
                    $table->dropColumn('lima_digit_terakhir_no_rangka');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Create captcha table back if needed
        Schema::create('captcha', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->string('captcha_code');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
};
