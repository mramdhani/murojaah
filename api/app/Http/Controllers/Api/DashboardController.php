<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemorizationProgress;
use App\Models\MemorizationSession;
use App\Models\ReviewLog;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard — Get dashboard summary for home page
     */
    public function index(): JsonResponse
    {
        $userId = 1;

        // Overall progress counts
        $progressCounts = MemorizationProgress::where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Today's review counts
        $todayCounts = ReviewLog::where('user_id', $userId)
            ->whereDate('reviewed_at', today())
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Total surahs ever reviewed
        $surahsReviewed = MemorizationProgress::where('user_id', $userId)
            ->distinct('surah_id')
            ->count('surah_id');

        // Last session for "Lanjutkan Terakhir"
        $lastSession = MemorizationSession::where('user_id', $userId)
            ->whereNull('ended_at')
            ->with(['surah:id,number,name_latin,name_arabic', 'currentAyah:id,ayah_number'])
            ->latest('started_at')
            ->first();

        return response()->json([
            'data' => [
                'overall' => [
                    'fluent' => $progressCounts['fluent'] ?? 0,
                    'doubtful' => $progressCounts['doubtful'] ?? 0,
                    'forgot' => $progressCounts['forgot'] ?? 0,
                    'total_reviewed' => array_sum($progressCounts),
                    'surahs_reviewed' => $surahsReviewed,
                ],
                'today' => [
                    'fluent' => $todayCounts['fluent'] ?? 0,
                    'doubtful' => $todayCounts['doubtful'] ?? 0,
                    'forgot' => $todayCounts['forgot'] ?? 0,
                    'total' => array_sum($todayCounts),
                ],
                'last_session' => $lastSession ? [
                    'id' => $lastSession->id,
                    'surah_id' => $lastSession->surah_id,
                    'surah_name' => $lastSession->surah->name_latin,
                    'surah_name_arabic' => $lastSession->surah->name_arabic,
                    'surah_number' => $lastSession->surah->number,
                    'current_ayah_number' => $lastSession->currentAyah->ayah_number,
                    'mode' => $lastSession->mode,
                ] : null,
            ],
        ]);
    }
}
