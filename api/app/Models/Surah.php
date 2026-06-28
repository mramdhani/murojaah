<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Surah extends Model
{
    protected $fillable = [
        'number',
        'name_arabic',
        'name_latin',
        'name_translation',
        'total_ayah',
        'revelation_place',
    ];

    protected $casts = [
        'number' => 'integer',
        'total_ayah' => 'integer',
    ];

    public function ayahs(): HasMany
    {
        return $this->hasMany(Ayah::class)->orderBy('ayah_number');
    }

    public function memorizationProgress(): HasMany
    {
        return $this->hasMany(MemorizationProgress::class);
    }

    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ReviewLog::class);
    }
}
