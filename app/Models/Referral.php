<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    protected $fillable = [
        'appointment_id',
        'patient_id',
        'from_doctor_id',
        'to_doctor_id',
        'reason',
        'summary_snapshot',
        'status',
        'responded_at',
        'urgency',
        'clinical_question',
        'decline_reason',
        'proposed_appointment_id',
    ];

    protected function casts(): array
    {
        return [
            'summary_snapshot' => 'array',
            'responded_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function fromDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'from_doctor_id');
    }

    public function toDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'to_doctor_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReferralReport::class);
    }

    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(AppointmentReport::class, 'referral_reports')
            ->withTimestamps();
    }

    public function proposedAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'proposed_appointment_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'sent' => 'Sent',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            'appointment_booked' => 'Appointment booked',
            default => ucfirst((string) $this->status),
        };
    }

    public function urgencyLabel(): string
    {
        return match ($this->urgency) {
            'soon' => 'Soon',
            'urgent' => 'Urgent',
            default => 'Routine',
        };
    }
}
