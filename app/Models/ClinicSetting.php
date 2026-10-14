<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicSetting extends Model
{
    protected $fillable = [
        'booking_horizon_months',
        'timezone',
        'minimum_notice_minutes',
        'cancellation_notice_hours',
        'payment_policy',
    ];

    protected function casts(): array
    {
        return [
            'booking_horizon_months' => 'integer',
            'minimum_notice_minutes' => 'integer',
            'cancellation_notice_hours' => 'integer',
        ];
    }

    public static function current(): self
    {
        $settings = static::query()->first();

        if ($settings) {
            return $settings;
        }

        return static::query()->create([
            'booking_horizon_months' => 6,
            'timezone' => (string) config('app.timezone', 'UTC'),
            'minimum_notice_minutes' => 0,
            'cancellation_notice_hours' => 0,
            'payment_policy' => 'none',
        ]);
    }
}
