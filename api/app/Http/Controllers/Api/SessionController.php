<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemorizationSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * POST /api/sessions — Start a new memorization session
     */
    public function store(Request $request): JsonResponse
    {
        $userId = 1;

        $validated = $request->validate([
            'surah_id' => 'required|integer|exists:surahs,id',
            'start_ayah_id' => 'required|integer|exists:ayahs,id',
            'mode' => 'required|in:memorization,murojaah,remote',
        ]);

        $session = MemorizationSession::create([
            'user_id' => $userId,
            'surah_id' => $validated['surah_id'],
            'start_ayah_id' => $validated['start_ayah_id'],
            'current_ayah_id' => $validated['start_ayah_id'],
            'mode' => $validated['mode'],
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Session started',
            'data' => [
                'id' => $session->id,
                'surah_id' => $session->surah_id,
                'current_ayah_id' => $session->current_ayah_id,
                'mode' => $session->mode,
            ],
        ], 201);
    }

    /**
     * PATCH /api/sessions/{id} — Update current ayah in session
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $userId = 1;

        $session = MemorizationSession::where('user_id', $userId)->findOrFail($id);

        $validated = $request->validate([
            'current_ayah_id' => 'sometimes|integer|exists:ayahs,id',
            'ended_at' => 'sometimes|date',
        ]);

        $session->update($validated);

        return response()->json([
            'message' => 'Session updated',
            'data' => [
                'id' => $session->id,
                'surah_id' => $session->surah_id,
                'current_ayah_id' => $session->current_ayah_id,
                'mode' => $session->mode,
            ],
        ]);
    }
}
