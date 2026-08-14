<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagEvalRun extends Model
{
    protected $fillable = [
        'model_used',
        'total_tests',
        'avg_faithfulness_score',
        'avg_answer_relevance_score',
        'avg_context_relevance_score',
        'overall_score',
        'avg_latency_seconds',
        'status',
        'results_payload',
        'error_message',
    ];

    protected $casts = [
        'total_tests' => 'integer',
        'avg_faithfulness_score' => 'float',
        'avg_answer_relevance_score' => 'float',
        'avg_context_relevance_score' => 'float',
        'overall_score' => 'float',
        'avg_latency_seconds' => 'float',
        'results_payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
