<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ayah;
use App\Models\MemorizationProgress;
use App\Models\ReviewLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * POST /api/reviews — Save a review status for an ayah
     */
    public function store(Request $request): JsonResponse
    {
        $userId = 1;

        $validated = $request->validate([
            'surah_id' => 'required|integer|exists:surahs,id',
            'ayah_id' => 'required|integer|exists:ayahs,id',
            'status' => 'required|in:forgot,doubtful,fluent',
            'was_revealed' => 'required|boolean',
        ]);

        $now = now();

        // Update or create memorization progress
        $progress = MemorizationProgress::updateOrCreate(
            [
                'user_id' => $userId,
                'surah_id' => $validated['surah_id'],
                'ayah_id' => $validated['ayah_id'],
            ],
            [
                'status' => $validated['status'],
                'last_reviewed_at' => $now,
            ]
        );

        // Increment review count
        $progress->increment('review_count');

        // Create review log entry
        ReviewLog::create([
            'user_id' => $userId,
            'surah_id' => $validated['surah_id'],
            'ayah_id' => $validated['ayah_id'],
            'status' => $validated['status'],
            'was_revealed' => $validated['was_revealed'],
            'reviewed_at' => $now,
        ]);

        // Get next ayah info
        $currentAyah = Ayah::find($validated['ayah_id']);
        $nextAyah = Ayah::where('surah_id', $validated['surah_id'])
            ->where('ayah_number', $currentAyah->ayah_number + 1)
            ->first();

        return response()->json([
            'message' => 'Review saved successfully',
            'data' => [
                'surah_id' => $validated['surah_id'],
                'ayah_id' => $validated['ayah_id'],
                'status' => $validated['status'],
                'next_ayah' => $nextAyah ? [
                    'surah_id' => $nextAyah->surah_id,
                    'ayah_number' => $nextAyah->ayah_number,
                ] : null,
            ],
        ]);
    }
}
