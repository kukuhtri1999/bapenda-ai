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
    'apakah_menang',
    'urutan_menang',
  ];

  protected $casts = [
    'apakah_menang' => 'boolean',
    'urutan_menang' => 'integer',
  ];
}
