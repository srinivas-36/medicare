<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAnalysis extends Model
{
    public const OPEN = [
        'queued',
        'preparing',
        'processing_reports',
        'generating',
        'validating',
    ];

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'initiated_by',
        'status',
        'progress',
        'analysis_type',
        'selected_report_ids',
        'context_snapshot',
        'structured_output',
        'original_summary',
        'clinician_summary',
        'approved_summary',
        'doctor_notes',
        'reviewed_findings',
        'provider',
        'model',
        'prompt_version',
        'error_code',
        'started_at',
        'completed_at',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'selected_report_ids' => 'array',
            'context_snapshot' => 'array',
            'structured_output' => 'array',
            'reviewed_findings' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(AiAnalysisAudit::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function hasStarted(): bool
    {
        return $this->started_at !== null || $this->status !== 'queued';
    }

    public function isRunning(): bool
    {
        return $this->isOpen() && $this->hasStarted() && ! $this->isStale();
    }

    public function isStale(): bool
    {
        return $this->isOpen()
            && $this->hasStarted()
            && $this->updated_at !== null
            && $this->updated_at->lt(now()->subSeconds(70));
    }

    public function safeError(): ?string
    {
        return match ($this->error_code) {
            'not_configured' => 'Analysis is not configured for this clinic.',
            'provider_error' => 'The analysis service did not return a result. You can retry.',
            'invalid_output' => 'The analysis response could not be validated. You can retry.',
            default => $this->error_code ? 'The analysis could not be completed. You can retry.' : null,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'queued' => 'Not started',
            'preparing', 'processing_reports', 'generating', 'validating' => 'Processing',
            'completed', 'failed' => $this->approved_at ? 'Approved' : 'Ready for review',
            default => 'Not started',
        };
    }
}
