<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if ($setting === null) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'float', 'decimal' => (float) $setting->value,
            'json' => json_decode((string) $setting->value, true),
            default => $setting->value,
        };
    }

    public static function setValue(string $key, mixed $value, string $type = 'string'): self
    {
        $stored = match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'type' => $type],
        );
    }
}
