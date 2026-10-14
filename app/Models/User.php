<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'email',
        'password',
        'phone',
        'photo',
        'gender',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'user';
        }

        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function receptionist(): HasOne
    {
        return $this->hasOne(Receptionist::class);
    }

    public function patient_profile(): HasOne
    {
        return $this->hasOne(PatientProfile::class);
    }

    public function doctorReviews(): HasMany
    {
        return $this->hasMany(DoctorReview::class);
    }

    public function appointmentsAsPatient(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function consultationsAsPatient(): HasMany
    {
        return $this->hasMany(Consultation::class, 'patient_id');
    }

    public function referralsAsPatient(): HasMany
    {
        return $this->hasMany(Referral::class, 'patient_id');
    }
}
