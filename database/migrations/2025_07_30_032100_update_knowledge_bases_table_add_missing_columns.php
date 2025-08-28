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
            // Add content fields
            $table->text('content')->nullable()->after('type');
            $table->text('excerpt')->nullable()->after('content');

            // Add source and file fields
            $table->enum('source_type', ['manual', 'file'])->default('manual')->after('excerpt');
            $table->string('file_path')->nullable()->after('source_type');
            $table->string('file_name')->nullable()->after('file_path');
            $table->string('file_type')->nullable()->after('file_name');
            $table->bigInteger('file_size')->nullable()->after('file_type');

            // Add status and priority
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->after('file_size');
            $table->integer('priority')->default(2)->after('status'); // 1=low, 2=normal, 3=high, 4=critical

            // Add metadata and search
            $table->json('tags')->nullable()->after('priority');
            $table->text('search_content')->nullable()->after('tags');
            $table->json('metadata')->nullable()->after('search_content');

            // Add tracking fields
            $table->integer('view_count')->default(0)->after('metadata');
            $table->timestamp('published_at')->nullable()->after('view_count');
            $table->unsignedBigInteger('created_by')->nullable()->after('published_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            // Add soft deletes
            $table->softDeletes()->after('updated_by');

            // Add foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Add indexes for better performance
            $table->index(['status', 'is_active']);
            $table->index(['source_type']);
            $table->index(['created_by']);
            $table->index(['view_count']);
            $table->index(['published_at']);

            // Full-text search index
            $table->fullText(['title', 'search_content'], 'knowledge_search_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            // Drop indexes
            $table->dropIndex(['status', 'is_active']);
            $table->dropIndex(['source_type']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['view_count']);
            $table->dropIndex(['published_at']);
            $table->dropFullText('knowledge_search_idx');

            // Drop columns
            $table->dropColumn([
                'content',
                'excerpt',
                'source_type',
                'file_path',
                'file_name',
                'file_type',
                'file_size',
                'status',
                'priority',
                'tags',
                'search_content',
                'metadata',
                'view_count',
                'published_at',
                'created_by',
                'updated_by',
                'deleted_at',
            ]);
        });
    }
};
