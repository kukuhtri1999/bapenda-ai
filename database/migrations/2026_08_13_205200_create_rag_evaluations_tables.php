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
        // 1. Golden Dataset for RAG Evaluation
        Schema::create('rag_eval_tests', function (Blueprint $table) {
            $table->id();
            $table->text('query');
            $table->enum('language', ['id', 'jv'])->default('id')->index();
            $table->string('expected_topic', 100)->nullable();
            $table->text('ground_truth')->nullable();
            $table->string('tags', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // 2. Evaluation Benchmark Runs History
        Schema::create('rag_eval_runs', function (Blueprint $table) {
            $table->id();
            $table->string('model_used', 100);
            $table->integer('total_tests')->default(0);
            $table->decimal('avg_faithfulness_score', 4, 2)->default(0.00);
            $table->decimal('avg_answer_relevance_score', 4, 2)->default(0.00);
            $table->decimal('avg_context_relevance_score', 4, 2)->default(0.00);
            $table->decimal('overall_score', 4, 2)->default(0.00);
            $table->decimal('avg_latency_seconds', 5, 2)->default(0.00);
            $table->enum('status', ['running', 'completed', 'failed'])->default('running')->index();
            $table->json('results_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rag_eval_runs');
        Schema::dropIfExists('rag_eval_tests');
    }
};
