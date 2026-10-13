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

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
