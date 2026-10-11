<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function experiences()
    {
        return $this->hasMany(DoctorExperience::class);
    }
    public function qualifications()
    {
        return $this->hasMany(DoctorQualification::class);
    }
}
