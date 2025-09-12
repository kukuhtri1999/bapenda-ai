<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisReport extends Model
{
  protected $fillable = [
    'start_date',
    'end_date',
    'chat_count',
    'summary_json',
    'combined_top_insight',
    'insight_summary',
    'recommendations',
    'recommendations_detailed',
    'status',
    'notes'
  ];

  protected $casts = [
    'summary_json' => 'array',
    'recommendations' => 'array',
    'recommendations_detailed' => 'array',
    'start_date' => 'datetime',
    'end_date' => 'datetime',
    'notes' => 'array',
  ];
}
