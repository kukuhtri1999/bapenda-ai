<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesertaLotre extends Model
{
  use HasFactory;

  protected $table = 'peserta_lotre';

  protected $fillable = [
    'nama',
    'nopol',
    'alamat',
    'kecamatan',
    'apakah_menang',
    'urutan_menang',
    'predetermined_winner_order',
  ];

  protected $casts = [
    'apakah_menang' => 'boolean',
    'urutan_menang' => 'integer',
    'predetermined_winner_order' => 'integer',
    'alamat' => 'string',
    'kecamatan' => 'string',
  ];

  /**
   * Scope for predetermined winners ordered by their set order
   */
  public function scopePredeterminedWinners($query)
  {
    return $query->whereNotNull('predetermined_winner_order')
      ->orderBy('predetermined_winner_order', 'asc');
  }

  /**
   * Scope for eligible participants (not yet won)
   */
  public function scopeEligible($query)
  {
    return $query->where('apakah_menang', false);
  }

  /**
   * Check if this participant is a predetermined winner
   */
  public function isPredeterminedWinner(): bool
  {
    return $this->predetermined_winner_order !== null;
  }
}
