<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ayah;
use App\Models\MemorizationProgress;
use App\Models\Surah;
use Illuminate\Http\JsonResponse;

class ProgressController extends Controller
{
    /**
     * GET /api/progress/surahs/{surahId} — Get progress detail for a surah
     */
    public function show(int $surahId): JsonResponse
    {
        $userId = 1;

        $surah = Surah::findOrFail($surahId);

        $ayahs = Ayah::where('surah_id', $surahId)
            ->orderBy('ayah_number')
            ->get();

        $progressMap = MemorizationProgress::where('user_id', $userId)
            ->where('surah_id', $surahId)
            ->get()
            ->keyBy('ayah_id');

        $summary = [
            'fluent' => 0,
            'doubtful' => 0,
            'forgot' => 0,
            'unreviewed' => 0,
        ];

        $ayahData = $ayahs->map(function ($ayah) use ($progressMap, &$summary) {
            $progress = $progressMap->get($ayah->id);
            $status = $progress ? $progress->status : 'unreviewed';
            $summary[$status]++;

            return [
                'ayah_number' => $ayah->ayah_number,
                'status' => $status,
                'last_reviewed_at' => $progress ? $progress->last_reviewed_at?->format('Y-m-d H:i:s') : null,
                'review_count' => $progress ? $progress->review_count : 0,
            ];
        });

        return response()->json([
            'data' => [
                'surah_id' => $surah->id,
                'surah_name' => $surah->name_latin,
                'surah_name_arabic' => $surah->name_arabic,
                'total_ayah' => $surah->total_ayah,
                'summary' => $summary,
                'ayahs' => $ayahData,
            ],
        ]);
    }
}
