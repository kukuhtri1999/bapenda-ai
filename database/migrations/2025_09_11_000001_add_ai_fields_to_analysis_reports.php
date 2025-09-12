<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('analysis_reports', function (Blueprint $table) {
            $table->longText('combined_top_insight')->nullable();
            $table->longText('insight_summary')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('recommendations_detailed')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('analysis_reports', function (Blueprint $table) {
            $table->dropColumn(['combined_top_insight', 'insight_summary', 'recommendations', 'recommendations_detailed']);
        });
    }
};
