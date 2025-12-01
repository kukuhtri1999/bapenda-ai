<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LotreSetting extends Model
{
  protected $table = 'lotre_settings';

  protected $fillable = [
    'key',
    'value',
    'type',
    'description',
  ];

  /**
   * Get a setting value by key
   */
  public static function getValue(string $key, $default = null)
  {
    $setting = Cache::remember("lotre_setting_{$key}", 3600, function () use ($key) {
      return self::where('key', $key)->first();
    });

    if (!$setting) {
      return $default;
    }

    return self::castValue($setting->value, $setting->type);
  }

  /**
   * Set a setting value
   */
  public static function setValue(string $key, $value, string $type = 'string', string $description = null): self
  {
    $setting = self::updateOrCreate(
      ['key' => $key],
      [
        'value' => is_array($value) ? json_encode($value) : (string) $value,
        'type' => $type,
        'description' => $description,
      ]
    );

    Cache::forget("lotre_setting_{$key}");

    return $setting;
  }

  /**
   * Cast value based on type
   */
  protected static function castValue($value, string $type)
  {
    return match ($type) {
      'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
      'integer' => (int) $value,
      'json' => json_decode($value, true),
      'array' => json_decode($value, true),
      default => $value,
    };
  }

  /**
   * Get the lotre mode (random or custom)
   */
  public static function getLotreMode(): string
  {
    return self::getValue('lotre_mode', 'random');
  }

  /**
   * Check if lotre is in custom mode
   */
  public static function isCustomMode(): bool
  {
    return self::getLotreMode() === 'custom';
  }

  /**
   * Get spin duration in milliseconds
   */
  public static function getSpinDuration(): int
  {
    return self::getValue('spin_duration_ms', 4000);
  }

  /**
   * Clear all settings cache
   */
  public static function clearCache(): void
  {
    $settings = self::all();
    foreach ($settings as $setting) {
      Cache::forget("lotre_setting_{$setting->key}");
    }
  }
}
