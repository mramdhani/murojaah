<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ayah extends Model
{
    protected $fillable = [
        'surah_id',
        'ayah_number',
        'text_arabic',
        'translation_id',
        'juz',
        'page',
    ];

    protected $casts = [
        'ayah_number' => 'integer',
        'juz' => 'integer',
        'page' => 'integer',
    ];

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }

    public function memorizationProgress(): HasMany
    {
        return $this->hasMany(MemorizationProgress::class);
    }
}
