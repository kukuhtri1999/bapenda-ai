<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Chat extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'title',
        'status',
        'metadata',
        'last_activity_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_activity_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($chat) {
            if (empty($chat->session_id)) {
                $chat->session_id = Str::uuid();
            }
        });
    }

    /**
     * Get all messages for this chat
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('sent_at');
    }

    /**
     * Get the user associated with this chat
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the latest message
     */
    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class)->latest('sent_at');
    }

    /**
     * Scope for active chats
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Update last activity timestamp
     */
    public function updateLastActivity()
    {
        $this->update(['last_activity_at' => now()]);
    }
}
