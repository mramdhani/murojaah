<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReviewLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewLogController extends Controller
{
    /**
     * GET /api/review-logs — Get review history
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $query = ReviewLog::where('user_id', $userId)
            ->with(['surah:id,name_latin,name_arabic', 'ayah:id,ayah_number'])
            ->orderByDesc('reviewed_at');

        // Filter by surah
        if ($request->has('surah_id')) {
            $query->where('surah_id', $request->integer('surah_id'));
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('reviewed_at', $request->input('date'));
        }

        $logs = $query->limit(200)->get();

        $data = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'surah_name' => $log->surah->name_latin,
                'surah_name_arabic' => $log->surah->name_arabic,
                'surah_id' => $log->surah_id,
                'ayah_number' => $log->ayah->ayah_number,
                'status' => $log->status,
                'was_revealed' => $log->was_revealed,
                'reviewed_at' => $log->reviewed_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json(['data' => $data]);
    }
}
