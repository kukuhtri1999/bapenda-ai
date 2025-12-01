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
    // Create lotre_settings table for storing configuration
    Schema::create('lotre_settings', function (Blueprint $table) {
      $table->id();
      $table->string('key')->unique();
      $table->text('value')->nullable();
      $table->string('type')->default('string'); // string, boolean, json, integer
      $table->string('description')->nullable();
      $table->timestamps();
    });

    // Add predetermined_winner_order column to peserta_lotre
    // This stores the pre-selected order for custom winner scenario
    Schema::table('peserta_lotre', function (Blueprint $table) {
      $table->integer('predetermined_winner_order')->nullable()->after('urutan_menang');
      $table->index('predetermined_winner_order');
    });

    // Insert default settings
    DB::table('lotre_settings')->insert([
      [
        'key' => 'lotre_mode',
        'value' => 'random',
        'type' => 'string',
        'description' => 'Mode lotre: random atau custom',
        'created_at' => now(),
        'updated_at' => now(),
      ],
      [
        'key' => 'spin_duration_ms',
        'value' => '4000',
        'type' => 'integer',
        'description' => 'Durasi animasi spin dalam milidetik',
        'created_at' => now(),
        'updated_at' => now(),
      ],
    ]);
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('peserta_lotre', function (Blueprint $table) {
      $table->dropIndex(['predetermined_winner_order']);
      $table->dropColumn('predetermined_winner_order');
    });

    Schema::dropIfExists('lotre_settings');
  }
};
