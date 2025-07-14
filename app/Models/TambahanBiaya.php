<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TambahanBiaya extends Model
{
    use SoftDeletes;

    protected $table = 'tambahan_biaya';

    protected $fillable = [
        'label_biaya',
        'harga_biaya',
        'id_data_pkb'
    ];

    protected $dates = ['deleted_at'];

    protected $casts = [
        'harga_biaya' => 'decimal:2'
    ];

    public function dataPkb(): BelongsTo
    {
        return $this->belongsTo(DataPkb::class, 'id_data_pkb');
    }
}
