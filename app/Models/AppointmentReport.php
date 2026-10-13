<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AppointmentReport extends Model
{
    protected $fillable = [
        'appointment_id',
        'group_name',
        'path',
        'original_name',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function referrals(): BelongsToMany
    {
        return $this->belongsToMany(Referral::class, 'referral_reports')->withTimestamps();
    }
}
