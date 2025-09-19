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
        Schema::table('knowledge_bases', function (Blueprint $table) {
            // Change content column from text to longtext to handle large documents
            $table->longText('content')->change();

            // Also change search_content to longtext for consistency
            $table->longText('search_content')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            // Revert back to text (note: this might cause data loss if content is too long)
            $table->text('content')->change();
            $table->text('search_content')->change();
        });
    }
};
