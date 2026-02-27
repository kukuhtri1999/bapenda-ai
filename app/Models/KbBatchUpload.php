<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbBatchUpload extends Model
{
  protected $fillable = [
    'user_id',
    'status',
    'total_files',
    'processed',
    'failed',
    'default_category',
    'default_type',
    'default_status',
    'files',
  ];

  protected $casts = [
    'files'       => 'array',
    'total_files' => 'integer',
    'processed'   => 'integer',
    'failed'      => 'integer',
  ];

  // Status constants
  const STATUS_PENDING   = 'pending';
  const STATUS_PROCESSING = 'processing';
  const STATUS_COMPLETED = 'completed';
  const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';
  const STATUS_FAILED    = 'failed';

  // File status constants
  const FILE_PENDING    = 'pending';
  const FILE_PROCESSING = 'processing';
  const FILE_DONE       = 'done';
  const FILE_FAILED     = 'failed';

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /** Progress 0–100 */
  public function getProgressAttribute(): int
  {
    if ($this->total_files === 0) return 0;
    return (int) round(($this->processed / $this->total_files) * 100);
  }
}
