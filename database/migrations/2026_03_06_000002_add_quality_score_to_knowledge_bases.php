<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Add quality_score and quality_scored_at columns to knowledge_bases.
   * - quality_score: AI-generated score 0.00 – 1.00
   * - quality_scored_at: when the score was last computed
   */
  public function up(): void
  {
    Schema::table('knowledge_bases', function (Blueprint $table) {
      $table->decimal('quality_score', 4, 2)
        ->nullable()
        ->after('search_content')
        ->comment('AI quality score 0.00 - 1.00');
      $table->timestamp('quality_scored_at')
        ->nullable()
        ->after('quality_score')
        ->comment('When the quality score was last computed');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('knowledge_bases', function (Blueprint $table) {
      $table->dropColumn(['quality_score', 'quality_scored_at']);
    });
  }
};
