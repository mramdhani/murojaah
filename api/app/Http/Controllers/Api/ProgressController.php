<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ayah;
use App\Models\MemorizationProgress;
use App\Models\Surah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    /**
     * GET /api/progress/surahs/{surahId} — Get progress detail for a surah
     */
    public function show(Request $request, int $surahId): JsonResponse
    {
        $userId = $request->user()->id;

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

    /**
     * DELETE /api/progress/surahs/{surahId} — Reset/delete progress and logs for a surah
     */
    public function destroy(Request $request, int $surahId): JsonResponse
    {
        $userId = $request->user()->id; // Default user ID for MVP

        // Delete memorization progress for all ayahs in this surah
        MemorizationProgress::where('user_id', $userId)
            ->where('surah_id', $surahId)
            ->delete();

        // Delete historical review logs for this surah's ayahs
        $ayahIds = Ayah::where('surah_id', $surahId)->pluck('id');
        \App\Models\ReviewLog::where('user_id', $userId)
            ->whereIn('ayah_id', $ayahIds)
            ->delete();

        return response()->json([
            'message' => 'Progress and review history for this surah has been deleted.',
        ]);
    }
}
