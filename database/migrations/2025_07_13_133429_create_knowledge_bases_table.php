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
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('question'); // FAQ question or document title
            $table->longText('answer'); // FAQ answer or document content
            $table->string('category'); // e.g., 'pajak', 'stnk', 'lokasi', 'syarat'
            $table->string('type')->default('faq'); // 'faq', 'sop', 'regulation'
            $table->json('keywords')->nullable(); // For better search
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher priority shown first
            $table->timestamps();

            $table->index(['category', 'type', 'is_active']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};
