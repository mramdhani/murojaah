<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ayah;
use App\Models\MemorizationProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AyahController extends Controller
{
    /**
     * GET /api/surahs/{surahId}/ayahs — Get all ayahs for a surah
     */
    public function index(Request $request, int $surahId): JsonResponse
    {
        $userId = $request->user()->id;

        $ayahs = Ayah::where('surah_id', $surahId)
            ->orderBy('ayah_number')
            ->get();

        // Get progress for all ayahs in this surah
        $progressMap = MemorizationProgress::where('user_id', $userId)
            ->where('surah_id', $surahId)
            ->get()
            ->keyBy('ayah_id');

        $data = $ayahs->map(function ($ayah) use ($progressMap) {
            $progress = $progressMap->get($ayah->id);

            return [
                'id' => $ayah->id,
                'surah_id' => $ayah->surah_id,
                'ayah_number' => $ayah->ayah_number,
                'text_arabic' => $ayah->text_arabic,
                'translation_id' => $ayah->translation_id,
                'progress_status' => $progress ? $progress->status : 'unreviewed',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/surahs/{surahId}/ayahs/{ayahNumber} — Get single ayah
     */
    public function show(Request $request, int $surahId, int $ayahNumber): JsonResponse
    {
        $userId = $request->user()->id;

        $ayah = Ayah::where('surah_id', $surahId)
            ->where('ayah_number', $ayahNumber)
            ->firstOrFail();

        $surah = $ayah->surah;

        $progress = MemorizationProgress::where('user_id', $userId)
            ->where('ayah_id', $ayah->id)
            ->first();

        return response()->json([
            'data' => [
                'id' => $ayah->id,
                'surah_id' => $ayah->surah_id,
                'surah_name' => $surah->name_latin,
                'surah_name_arabic' => $surah->name_arabic,
                'surah_number' => $surah->number,
                'ayah_number' => $ayah->ayah_number,
                'total_ayah' => $surah->total_ayah,
                'text_arabic' => $ayah->text_arabic,
                'translation_id' => $ayah->translation_id,
                'progress_status' => $progress ? $progress->status : 'unreviewed',
            ],
        ]);
    }
}
