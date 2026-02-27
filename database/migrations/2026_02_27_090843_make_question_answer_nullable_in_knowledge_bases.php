<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes the legacy `question` and `answer` columns nullable so that
     * file-upload entries (where content is extracted server-side) do not
     * crash with a NOT NULL constraint violation.
     */
    public function up(): void
    {
        // Back-fill any existing rows that have null/empty question or answer
        // before altering the column type to avoid data issues on older rows.
        DB::statement("UPDATE knowledge_bases SET question = title WHERE (question IS NULL OR question = '')");
        DB::statement("UPDATE knowledge_bases SET answer = COALESCE(content, title) WHERE (answer IS NULL OR answer = '')");

        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->text('question')->nullable()->change();
            $table->longText('answer')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Back-fill before reverting so the NOT NULL constraint can be restored
        DB::statement("UPDATE knowledge_bases SET question = title WHERE (question IS NULL OR question = '')");
        DB::statement("UPDATE knowledge_bases SET answer = COALESCE(content, title) WHERE (answer IS NULL OR answer = '')");

        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->text('question')->nullable(false)->change();
            $table->longText('answer')->nullable(false)->change();
        });
    }
};
