<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WajibPajak extends Model
{
    use SoftDeletes;

    protected $table = 'wajib_pajak';

    protected $fillable = [
        'nama',
        'nopol',
        'lima_digit_terakhir_no_rangka',
        'nomer_wa'
    ];

    protected $dates = ['deleted_at'];

    public function dataPkb(): HasMany
    {
        return $this->hasMany(DataPkb::class, 'id_wajib_pajak');
    }
}
