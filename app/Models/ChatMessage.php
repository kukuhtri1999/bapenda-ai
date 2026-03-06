<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;
    protected $fillable = [
        'chat_id',
        'role',
        'content',
        'answer',
        'topic',
        'sentiment',
        'response_time_seconds',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($message) {
            if (empty($message->sent_at)) {
                $message->sent_at = now();
            }
        });
    }

    /**
     * Get the chat that owns this message
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Scope for user messages
     */
    public function scopeFromUser($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope for assistant messages
     */
    public function scopeFromAssistant($query)
    {
        return $query->where('role', 'assistant');
    }

    /**
     * Check if message is from user
     */
    public function isFromUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if message is from assistant
     */
    public function isFromAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
