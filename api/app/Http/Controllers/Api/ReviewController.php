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
        $userId = $request->user()->id;

        $validated = $request->validate([
            'surah_id' => 'required|integer|exists:surahs,id',
            'ayah_id' => 'required|integer|exists:ayahs,id',
            'status' => 'required|in:forgot,doubtful,fluent',
            'was_revealed' => 'required|boolean',
        ]);

        $now = now();

        // Ambil data progress lama jika ada untuk menghitung review_count berikutnya
        $existingProgress = MemorizationProgress::where([
            'user_id' => $userId,
            'surah_id' => $validated['surah_id'],
            'ayah_id' => $validated['ayah_id'],
        ])->first();

        $reviewCount = $existingProgress ? $existingProgress->review_count + 1 : 1;
        $status = $validated['status'];

        // Algoritma Spaced Repetition sederhana:
        // - Lupa (forgot) -> review besok (+1 hari)
        // - Ragu-ragu (doubtful) -> review 3 hari lagi (+3 hari)
        // - Lancar (fluent) -> jika review_count <= 1: 7 hari; <= 3: 14 hari; > 3: 30 hari
        $days = 1;
        if ($status === 'forgot') {
            $days = 1;
        } elseif ($status === 'doubtful') {
            $days = 3;
        } elseif ($status === 'fluent') {
            if ($reviewCount <= 1) {
                $days = 7;
            } elseif ($reviewCount <= 3) {
                $days = 14;
            } else {
                $days = 30;
            }
        }

        $nextReviewAt = now()->addDays($days)->startOfDay();

        // Update or create memorization progress
        $progress = MemorizationProgress::updateOrCreate(
            [
                'user_id' => $userId,
                'surah_id' => $validated['surah_id'],
                'ayah_id' => $validated['ayah_id'],
            ],
            [
                'status' => $status,
                'last_reviewed_at' => $now,
                'next_review_at' => $nextReviewAt,
                'review_count' => $reviewCount,
            ]
        );

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
