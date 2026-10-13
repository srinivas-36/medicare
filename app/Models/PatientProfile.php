<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    protected $fillable = [
        'user_id',
        'age',
        'blood_type',
        'health_notes',
    ];

    protected function casts(): array
    {
        return [
            'health_notes' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
