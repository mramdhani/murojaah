<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemorizationSession extends Model
{
    protected $fillable = [
        'user_id',
        'surah_id',
        'start_ayah_id',
        'current_ayah_id',
        'mode',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }

    public function startAyah(): BelongsTo
    {
        return $this->belongsTo(Ayah::class, 'start_ayah_id');
    }

    public function currentAyah(): BelongsTo
    {
        return $this->belongsTo(Ayah::class, 'current_ayah_id');
    }
}
