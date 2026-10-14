<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'clinical_notes',
        'diagnosis',
        'plan',
        'ai_status',
        'ai_summary',
        'ai_analysis',
        'ai_generated_at',
        'follow_up_on',
        'follow_up_instructions',
    ];

    protected function casts(): array
    {
        return [
            'ai_analysis' => 'array',
            'ai_generated_at' => 'datetime',
            'follow_up_on' => 'date',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
