<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatFeedback extends Model
{
    protected $table = 'chat_feedback';

    protected $fillable = [
        'session_id',
        'nama',
        'nopol',
        'nomer_wa',
        'rating',
        'feedback_text',
        'chat_summary',
        'chat_ended_at'
    ];

    protected $casts = [
        'chat_summary' => 'array',
        'chat_ended_at' => 'datetime',
        'rating' => 'integer'
    ];

    /**
     * Get the chat session associated with this feedback
     */
    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'session_id', 'session_id');
    }
}
