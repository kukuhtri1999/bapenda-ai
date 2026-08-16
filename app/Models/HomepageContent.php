<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class HomepageContent extends Model
{
    protected $fillable = [
        'section',
        'key',
        'value',
        'type',
        'label',
        'description',
    ];

    /**
     * Boot model events for automatic cache management.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('homepage_contents_grouped');
            Cache::forget('homepage_contents_all');
        });

        static::deleted(function () {
            Cache::forget('homepage_contents_grouped');
            Cache::forget('homepage_contents_all');
        });
    }

    /**
     * Get a specific content value with optional fallback default.
     */
    public static function getValue(string $key, $default = null)
    {
        $all = static::getAllGrouped();
        return $all[$key] ?? $default;
    }

    /**
     * Get all content parsed and keyed by unique key (cached for high performance).
     */
    public static function getAllGrouped(): array
    {
        return Cache::remember('homepage_contents_grouped', 3600, function () {
            $items = static::all();
            $result = [];
            foreach ($items as $item) {
                $value = $item->value;
                if ($item->type === 'json' && is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
                } elseif ($item->type === 'boolean') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                $result[$item->key] = $value;
            }
            return $result;
        });
    }

    /**
     * Get all raw items grouped by section for the Admin CMS form.
     */
    public static function getAdminGrouped(): array
    {
        $items = static::orderBy('id')->get();
        $sections = [];
        foreach ($items as $item) {
            $val = $item->value;
            if ($item->type === 'json' && is_string($val)) {
                $decoded = json_decode($val, true);
                $val = json_last_error() === JSON_ERROR_NONE ? $decoded : $val;
            }
            $sections[$item->section][] = [
                'id' => $item->id,
                'section' => $item->section,
                'key' => $item->key,
                'value' => $val,
                'type' => $item->type,
                'label' => $item->label,
                'description' => $item->description,
            ];
        }
        return $sections;
    }
}
