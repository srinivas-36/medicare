<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysisAudit extends Model
{
    protected $fillable = [
        'ai_analysis_id',
        'user_id',
        'action',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(AiAnalysis::class, 'ai_analysis_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
