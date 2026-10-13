<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorExperience extends Model
{
    protected $fillable = [
        'doctor_id',
        'designation',
        'hospital_name',
        'location',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
