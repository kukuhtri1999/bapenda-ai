<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'options',
        'validation_rules',
        'sort_order',
        'is_public',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'options' => 'array',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function ($setting) {
            // Clear cache when setting is updated
            Cache::forget('app_settings');
            Cache::forget("app_setting_{$setting->key}");
        });

        static::deleted(function ($setting) {
            // Clear cache when setting is deleted
            Cache::forget('app_settings');
            Cache::forget("app_setting_{$setting->key}");
        });
    }

    /**
     * Get the creator of the setting
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater of the setting
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for public settings
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for specific group
     */
    public function scopeGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get formatted value based on type
     */
    public function getFormattedValueAttribute()
    {
        return $this->formatValue($this->value, $this->type);
    }

    /**
     * Format value based on type
     */
    public function formatValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return json_decode($value, true);
            case 'array':
                return is_array($value) ? $value : json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Get setting value by key with caching
     */
    public static function get($key, $default = null)
    {
        $cacheKey = "app_setting_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->active()->first();

            if (!$setting) {
                return $default;
            }

            return $setting->formatValue($setting->value, $setting->type);
        });
    }

    /**
     * Set setting value
     */
    public static function set($key, $value, $type = 'string')
    {
        $setting = static::firstOrNew(['key' => $key]);

        // Convert value to string for storage
        if ($type === 'json' || $type === 'array') {
            $value = json_encode($value);
        } elseif ($type === 'boolean') {
            $value = $value ? 'true' : 'false';
        }

        $setting->value = $value;
        $setting->type = $type;
        $setting->updated_by = Auth::check() ? Auth::id() : null;

        if (!$setting->exists) {
            $setting->created_by = Auth::check() ? Auth::id() : null;
        }

        $setting->save();

        return $setting;
    }

    /**
     * Get all settings grouped by group
     */
    public static function getAllGrouped($publicOnly = false)
    {
        $cacheKey = $publicOnly ? 'app_settings_public' : 'app_settings';

        return Cache::remember($cacheKey, 3600, function () use ($publicOnly) {
            $query = static::active()->orderBy('group')->orderBy('sort_order');

            if ($publicOnly) {
                $query->public();
            }

            return $query->get()->groupBy('group')->map(function ($settings) {
                return $settings->mapWithKeys(function ($setting) {
                    return [$setting->key => $setting->formatValue($setting->value, $setting->type)];
                });
            });
        });
    }

    /**
     * Get settings for specific group
     */
    public static function getGroup($group, $publicOnly = false)
    {
        $allSettings = static::getAllGrouped($publicOnly);
        return $allSettings->get($group, collect());
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache()
    {
        Cache::forget('app_settings');
        Cache::forget('app_settings_public');

        // Clear individual setting caches
        static::all()->each(function ($setting) {
            Cache::forget("app_setting_{$setting->key}");
        });
    }

    /**
     * Get validation rules for the setting
     */
    public function getValidationRules()
    {
        if (!$this->validation_rules) {
            return [];
        }

        return explode('|', $this->validation_rules);
    }
}
