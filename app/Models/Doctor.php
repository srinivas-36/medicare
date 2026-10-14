<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $fillable = [
        'user_id',
        'specialization',
        'ticket_price',
        'bio',
        'about',
        'approval_status',
        'average_rating',
        'total_rating',
        'appointment_duration',
        'appointment_buffer',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'ticket_price' => 'decimal:2',
            'average_rating' => 'decimal:2',
            'appointment_duration' => 'integer',
            'appointment_buffer' => 'integer',
        ];
    }

    public function getAttribute($key)
    {
        if ($key === 'ticketPrice') {
            return number_format((float) ($this->attributes['ticket_price'] ?? 0), 2, '.', '');
        }

        if ($key === 'isApproved') {
            return $this->attributes['approval_status'] ?? null;
        }

        if ($key === 'averageRating') {
            return $this->attributes['average_rating'] ?? 0;
        }

        if ($key === 'totalRating') {
            return $this->attributes['total_rating'] ?? 0;
        }

        if (in_array($key, ['name', 'email', 'phone', 'photo', 'gender'], true)) {
            return $this->user?->{$key};
        }

        return parent::getAttribute($key);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receptionists(): HasMany
    {
        return $this->hasMany(Receptionist::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(DoctorExperience::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(DoctorQualification::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(DoctorAvailabilityException::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DoctorReview::class);
    }

    public function registeredPatients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'doctor_patients', 'doctor_id', 'patient_id')
            ->withPivot(['origin', 'added_by'])
            ->withTimestamps();
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function referralsSent(): HasMany
    {
        return $this->hasMany(Referral::class, 'from_doctor_id');
    }

    public function referralsReceived(): HasMany
    {
        return $this->hasMany(Referral::class, 'to_doctor_id');
    }
}
