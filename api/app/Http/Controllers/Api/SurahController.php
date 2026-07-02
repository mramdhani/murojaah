<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Surah;
use App\Models\MemorizationProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Models\Ayah;

class SurahController extends Controller
{
    /**
     * GET /api/surahs — List all surahs with progress summary
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $surahs = Surah::orderBy('number')->get();

        // Get progress counts grouped by surah
        $progressCounts = MemorizationProgress::where('user_id', $userId)
            ->selectRaw('surah_id, status, COUNT(*) as count')
            ->groupBy('surah_id', 'status')
            ->get()
            ->groupBy('surah_id');

        // Fetch Juz range for all surahs in a single query
        $juzRanges = Ayah::selectRaw('surah_id, MIN(juz) as juz_start, MAX(juz) as juz_end, MIN(page) as page_start, MAX(page) as page_end')
            ->groupBy('surah_id')
            ->get()
            ->keyBy('surah_id');

        $data = $surahs->map(function ($surah) use ($progressCounts, $juzRanges) {
            $progress = $progressCounts->get($surah->id, collect());
            $statusCounts = $progress->pluck('count', 'status')->toArray();
            
            $juz = $juzRanges->get($surah->id);

            return [
                'id' => $surah->id,
                'number' => $surah->number,
                'name_latin' => $surah->name_latin,
                'name_arabic' => $surah->name_arabic,
                'name_translation' => $surah->name_translation,
                'total_ayah' => $surah->total_ayah,
                'revelation_place' => $surah->revelation_place,
                'juz_start' => $juz ? (int) $juz->juz_start : null,
                'juz_end' => $juz ? (int) $juz->juz_end : null,
                'page_start' => $juz ? (int) $juz->page_start : null,
                'page_end' => $juz ? (int) $juz->page_end : null,
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
