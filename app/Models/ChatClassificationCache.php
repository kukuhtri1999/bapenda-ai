<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatClassificationCache extends Model
{
    use HasFactory;

    protected $table = 'chat_classification_cache';
    protected $fillable = [
        'text_hash',
        'category',
        'sentiment',
        'confidence',
        'snippet',
        'last_used_at'
    ];
    public $timestamps = true;
}
