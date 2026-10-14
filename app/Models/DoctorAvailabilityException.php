<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorAvailabilityException extends Model
{
    protected $fillable = [
        'doctor_id',
        'starts_on',
        'ends_on',
        'kind',
        'starts_at',
        'ends_at',
        'title',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
