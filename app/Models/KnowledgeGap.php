<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class KnowledgeGap extends Model
{
    protected $fillable = [
        'query',
        'normalized_query',
        'source',
        'similarity_score',
        'frequency',
        'session_id',
        'status',
        'draft_kb_id',
        'suggested_draft',
        'last_seen_at',
    ];

    protected $casts = [
        'similarity_score' => 'float',
        'frequency' => 'integer',
        'suggested_draft' => 'array',
        'last_seen_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to the KnowledgeBase entry that resolved this gap.
     */
    public function draftKnowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class, 'draft_kb_id');
    }

    /**
     * Relationship to the Chat session where this gap was detected.
     */
    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'session_id', 'session_id');
    }

    /**
     * Scope for pending gaps.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for resolved gaps.
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope for dismissed gaps.
     */
    public function scopeDismissed(Builder $query): Builder
    {
        return $query->where('status', 'dismissed');
    }
}
