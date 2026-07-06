<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MushafWord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'word_id',
        'verse_key',
        'surah_number',
        'ayah_number',
        'position',
        'page_number',
        'font_page',
        'line_number',
        'char_type',
        'code_v2',
    ];

    protected $casts = [
        'word_id'      => 'integer',
        'surah_number' => 'integer',
        'ayah_number'  => 'integer',
        'position'     => 'integer',
        'page_number'  => 'integer',
        'font_page'    => 'integer',
        'line_number'  => 'integer',
    ];

    public function scopeByPage($query, int $page)
    {
        return $query->where('page_number', $page)
            ->orderBy('surah_number')
            ->orderBy('ayah_number')
            ->orderBy('position');
    }
}
