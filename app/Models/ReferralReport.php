<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralReport extends Model
{
    protected $fillable = [
        'referral_id',
        'appointment_report_id',
    ];

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function appointmentReport(): BelongsTo
    {
        return $this->belongsTo(AppointmentReport::class);
    }
}
