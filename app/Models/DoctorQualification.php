<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorQualification extends Model
{
    protected $fillable = [
        'doctor_id',
        'degree',
        'institution',
        'start_date',
        'end_date',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
