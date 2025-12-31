<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'setting_key',
        'setting_group',
        'setting_value',
        'data_type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Helper methods
    public function getCastedValue(): mixed
    {
        return match($this->data_type) {
            'integer' => (int) $this->setting_value,
            'float', 'decimal' => (float) $this->setting_value,
            'boolean' => filter_var($this->setting_value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('setting_key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return $setting->getCastedValue();
    }

    public static function setValue(string $key, mixed $value, string $dataType = 'string'): void
    {
        $preparedValue = match($dataType) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        static::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $preparedValue,
                'data_type' => $dataType,
            ]
        );
    }
}
