<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    /**
     * Topics offered on the public contact form.
     *
     * @var array<string, string>
     */
    public const SUBJECTS = [
        'booking' => 'Booking a visit',
        'appointment' => 'An existing appointment',
        'account' => 'My account',
        'billing' => 'Fees and cancellation',
        'other' => 'Something else',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'subject',
        'body',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjectLabel(): string
    {
        return self::SUBJECTS[$this->subject] ?? $this->subject;
    }
}
