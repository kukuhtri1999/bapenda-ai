<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KnowledgeBase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'question',
        'answer',
        'excerpt',
        'content',
        'category',
        'type',
        'source_type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'images',
        'metadata',
        'keywords',
        'tags',
        'is_active',
        'priority',
        'published_at',
        'status',
        'created_by',
        'updated_by',
        'search_content',
    ];

    protected $casts = [
        'keywords' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'view_count' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'images' => 'array',
    ];

    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->generateSearchContent();
        });

        static::updating(function ($model) {
            $model->generateSearchContent();
        });

        static::created(function ($model) {
            $model->increment('view_count', 0); // Initialize view count
        });
    }

    /**
     * Generate searchable content from title, excerpt, and content
     */
    public function generateSearchContent()
    {
        $searchContent = collect([
            $this->title,
            // prefer explicit excerpt if present
            $this->attributes['excerpt'] ?? null,
            // include both new and legacy body fields
            strip_tags((string)$this->content),
            strip_tags((string)$this->answer),
            // include legacy question/title variant
            $this->question,
            $this->category,
            is_array($this->keywords) ? implode(' ', $this->keywords) : '',
            // tags may be an array (JSON column) or string
            is_array($this->tags) ? implode(' ', $this->tags) : ($this->tags ?? ''),
        ])->filter()->implode(' ');

        $this->search_content = $searchContent;
    }

    /**
     * Relationships
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSourceType($query, $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
                ->orWhere('question', 'LIKE', "%{$term}%")
                ->orWhere('excerpt', 'LIKE', "%{$term}%")
                ->orWhere('content', 'LIKE', "%{$term}%")
                ->orWhere('answer', 'LIKE', "%{$term}%")
                ->orWhere('search_content', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Accessors & Mutators
     */
    public function getExcerptAttribute($value)
    {
        return $value ?: Str::limit(strip_tags($this->content), 200);
    }

    public function getFormattedSizeAttribute()
    {
        if (!$this->file_size) return null;

        $bytes = floatval($this->file_size);
        $units = ['B', 'KB', 'MB', 'GB'];
        $precision = 2;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    // Removed CSV-based tag mutator/accessor; using JSON cast instead

    /**
     * Helper methods
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function isManual()
    {
        return $this->source_type === 'manual';
    }

    public function isFile()
    {
        return $this->source_type === 'file';
    }

    public function getFileUrl()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    /**
     * Static methods for categories and types
     */
    public static function getCategories()
    {
        return [
            'pajak' => 'Pajak Kendaraan',
            'stnk' => 'STNK & BPKB',
            'lokasi' => 'Lokasi & Jam Operasional',
            'syarat' => 'Syarat & Dokumen',
            'tarif' => 'Tarif & Biaya',
            'prosedur' => 'Prosedur & Tata Cara',
            'denda' => 'Denda & Sanksi',
            'online' => 'Layanan Online',
            'umum' => 'Informasi Umum',
        ];
    }

    public static function getTypes()
    {
        return [
            'faq' => 'FAQ (Frequently Asked Questions)',
            'sop' => 'SOP (Standard Operating Procedure)',
            'regulation' => 'Peraturan & Kebijakan',
            'guide' => 'Panduan & Tutorial',
            'announcement' => 'Pengumuman',
        ];
    }

    public static function getStatuses()
    {
        return [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ];
    }
}
