<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Captcha extends Model
{
    use SoftDeletes;

    protected $table = 'captcha';

    protected $fillable = [
        'jawaban_captcha'
    ];

    protected $dates = ['deleted_at'];
}
