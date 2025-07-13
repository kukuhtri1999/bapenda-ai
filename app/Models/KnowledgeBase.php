<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $fillable = [
        'title',
        'question',
        'answer',
        'category',
        'type',
        'keywords',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Scope for active knowledge base entries
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for specific type
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'LIKE', "%{$searchTerm}%")
                ->orWhere('question', 'LIKE', "%{$searchTerm}%")
                ->orWhere('answer', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Scope for priority order
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
    }
}
