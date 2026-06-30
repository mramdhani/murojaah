<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemorizationProgress;
use App\Models\MemorizationSession;
use App\Models\ReviewLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard — Get dashboard summary for home page
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

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

        // 1. Antrian Murojaah Hari Ini (due_today)
        $dueSurahs = MemorizationProgress::where('user_id', $userId)
            ->where('next_review_at', '<=', now())
            ->join('surahs', 'memorization_progress.surah_id', '=', 'surahs.id')
            ->selectRaw('surahs.id, surahs.number, surahs.name_latin, surahs.name_arabic, COUNT(*) as due_count')
            ->groupBy('surahs.id', 'surahs.number', 'surahs.name_latin', 'surahs.name_arabic')
            ->orderBy('surahs.number')
            ->get();

        // 2. Perhitungan Streak Hari Berturut-turut
        $reviewDates = ReviewLog::where('user_id', $userId)
            ->selectRaw('DATE(reviewed_at) as review_date')
            ->groupBy('review_date')
            ->orderBy('review_date', 'desc')
            ->pluck('review_date')
            ->map(fn($date) => \Carbon\Carbon::parse($date))
            ->toArray();

        $streak = 0;
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        if (!empty($reviewDates)) {
            $latestReviewDate = $reviewDates[0]->startOfDay();
            
            // Streak aktif jika user murojaah hari ini atau kemarin
            if ($latestReviewDate->eq($today) || $latestReviewDate->eq($yesterday)) {
                $streak = 1;
                $currentDate = $latestReviewDate;

                for ($i = 1; $i < count($reviewDates); $i++) {
                    $prevDate = $reviewDates[$i]->startOfDay();
                    if ($currentDate->diffInDays($prevDate) === 1) {
                        $streak++;
                        $currentDate = $prevDate;
                    } else {
                        break;
                    }
                }
            }
        }

        // 3. Kalender Heatmap Aktivitas (22 Minggu Terakhir)
        $startDate = now()->subWeeks(22)->startOfWeek();
        $heatmapData = ReviewLog::where('user_id', $userId)
            ->where('reviewed_at', '>=', $startDate)
            ->selectRaw('DATE(reviewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

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
                'due_today' => $dueSurahs->map(fn($s) => [
                    'id' => $s->id,
                    'number' => $s->number,
                    'surah_name' => $s->name_latin,
                    'surah_name_arabic' => $s->name_arabic,
                    'due_count' => $s->due_count,
                ]),
                'streak' => $streak,
                'heatmap' => (object)$heatmapData,
            ],
        ]);
    }
}
