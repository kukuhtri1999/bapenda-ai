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
    'status',
    'notes'
  ];

  protected $casts = [
    'summary_json' => 'array',
    'start_date' => 'datetime',
    'end_date' => 'datetime',
  ];
}
