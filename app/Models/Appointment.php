<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_date',
        'appointment_time',
        'status',
        'reason',
        'visit_type',
        'ending_time',
        'ticket_price',
        'payment_status',
        'stripe_session_id',
        'stripe_payment_intent',
        'paid_at',
        'notes',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'ticket_price' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(AppointmentReport::class);
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function startsAt(): Carbon
    {
        return Carbon::parse($this->appointment_date->toDateString().' '.$this->appointment_time);
    }

    public function canPatientCancel(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true)
            && $this->startsAt()->isFuture();
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereDate('appointment_date', '>', today()->toDateString())
                ->orWhere(function (Builder $query) {
                    $query->whereDate('appointment_date', today()->toDateString())
                        ->where('appointment_time', '>=', now()->format('H:i:s'));
                });
        });
    }

    public function applyStatus(string $status, User $actor, ?string $cancelReason = null): void
    {
        if (in_array($this->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'This appointment can no longer be changed.',
            ]);
        }

        if (! in_array($status, ['confirmed', 'completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'That status is not allowed.',
            ]);
        }

        $this->status = $status;

        if ($status === 'cancelled') {
            $this->cancelled_by = $actor->id;
            $this->cancel_reason = $cancelReason;
        }

        $this->save();
    }
}
