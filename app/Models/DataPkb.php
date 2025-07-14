<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataPkb extends Model
{
    use SoftDeletes;

    protected $table = 'data_pkb';

    protected $fillable = [
        'id_wajib_pajak',
        'nopol',
        'warna',
        'model',
        'merk',
        'type',
        'tahun',
        'tanggal_masa_pajak',
        'pkb',
        'opsen_pkb',
        'pkb_progresif',
        'opsen_pkb_prog',
        'swdkllj',
        'parkir_berlangganan',
        'pengesahan_stnk',
        'total'
    ];

    protected $dates = ['deleted_at', 'tanggal_masa_pajak'];

    protected $casts = [
        'pkb' => 'decimal:2',
        'opsen_pkb' => 'decimal:2',
        'pkb_progresif' => 'decimal:2',
        'opsen_pkb_prog' => 'decimal:2',
        'swdkllj' => 'decimal:2',
        'parkir_berlangganan' => 'decimal:2',
        'pengesahan_stnk' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function wajibPajak(): BelongsTo
    {
        return $this->belongsTo(WajibPajak::class, 'id_wajib_pajak');
    }

    public function tambahanBiaya(): HasMany
    {
        return $this->hasMany(TambahanBiaya::class, 'id_data_pkb');
    }
}
