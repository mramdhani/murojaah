<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemorizationProgress extends Model
{
    protected $table = 'memorization_progress';

    protected $fillable = [
        'user_id',
        'surah_id',
        'ayah_id',
        'status',
        'last_reviewed_at',
        'review_count',
    ];

    protected $casts = [
        'review_count' => 'integer',
        'last_reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }

    public function ayah(): BelongsTo
    {
        return $this->belongsTo(Ayah::class);
    }
}
