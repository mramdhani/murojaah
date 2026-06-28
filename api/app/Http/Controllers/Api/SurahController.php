<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Surah;
use App\Models\MemorizationProgress;
use Illuminate\Http\JsonResponse;

class SurahController extends Controller
{
    /**
     * GET /api/surahs — List all surahs with progress summary
     */
    public function index(): JsonResponse
    {
        $userId = 1; // Default user for MVP

        $surahs = Surah::orderBy('number')->get();

        // Get progress counts grouped by surah
        $progressCounts = MemorizationProgress::where('user_id', $userId)
            ->selectRaw('surah_id, status, COUNT(*) as count')
            ->groupBy('surah_id', 'status')
            ->get()
            ->groupBy('surah_id');

        $data = $surahs->map(function ($surah) use ($progressCounts) {
            $progress = $progressCounts->get($surah->id, collect());
            $statusCounts = $progress->pluck('count', 'status')->toArray();

            return [
                'id' => $surah->id,
                'number' => $surah->number,
                'name_latin' => $surah->name_latin,
                'name_arabic' => $surah->name_arabic,
                'name_translation' => $surah->name_translation,
                'total_ayah' => $surah->total_ayah,
                'revelation_place' => $surah->revelation_place,
                'progress' => [
                    'fluent' => $statusCounts['fluent'] ?? 0,
                    'doubtful' => $statusCounts['doubtful'] ?? 0,
                    'forgot' => $statusCounts['forgot'] ?? 0,
                    'unreviewed' => $surah->total_ayah - array_sum($statusCounts),
                ],
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/surahs/{id} — Get surah detail
     */
    public function show(int $id): JsonResponse
    {
        $surah = Surah::findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $surah->id,
                'number' => $surah->number,
                'name_latin' => $surah->name_latin,
                'name_arabic' => $surah->name_arabic,
                'name_translation' => $surah->name_translation,
                'total_ayah' => $surah->total_ayah,
                'revelation_place' => $surah->revelation_place,
            ],
        ]);
    }
}
