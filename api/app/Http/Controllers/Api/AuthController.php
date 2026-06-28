<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MemorizationProgress;
use App\Models\ReviewLog;
use App\Models\MemorizationSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * POST /api/auth/guest — Create an anonymous guest user
     */
    public function guest(Request $request): JsonResponse
    {
        $uuid = (string) Str::uuid();
        
        $guest = User::create([
            'name' => 'Tamu Murojaah',
            'email' => 'guest_' . $uuid . '@murojaah.com',
            'password' => bcrypt(Str::random(16)),
        ]);

        $token = $guest->createToken('guest_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $guest->id,
                'name' => $guest->name,
                'email' => $guest->email,
                'is_guest' => true,
            ]
        ], 201);
    }

    /**
     * GET /api/auth/google/redirect — Redirect to Google OAuth
     */
    public function redirectToGoogle(Request $request)
    {
        // Menyimpan ID guest saat ini ke query state agar bisa dimerge pas callback
        $guestId = $request->query('guest_id');

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $guestId])
            ->redirect();
    }

    /**
     * GET /api/auth/google/callback — Handle Google callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Cari atau buat user berdasarkan google_id atau email
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($user) {
                // Update info google jika email cocok tapi google_id belum terisi
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                    ]);
                }
            } else {
                // Buat user baru
                $user = User::create([
                    'name' => $googleUser->getName() ?? 'User Murojaah',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => bcrypt(Str::random(16)), // Dummy password
                ]);
            }

            // Integrasi Data Guest (Merge) jika ada State Guest ID
            $guestId = $request->input('state');
            if ($guestId && $guestId != $user->id) {
                $guestUser = User::find($guestId);
                if ($guestUser && str_contains($guestUser->email, 'guest_')) {
                    
                    // 1. Merge progress (memorization_progress) - hati-hati unique constraint
                    $guestProgresses = MemorizationProgress::where('user_id', $guestUser->id)->get();
                    foreach ($guestProgresses as $guestProgress) {
                        $existingProgress = MemorizationProgress::where('user_id', $user->id)
                            ->where('surah_id', $guestProgress->surah_id)
                            ->where('ayah_id', $guestProgress->ayah_id)
                            ->first();

                        if ($existingProgress) {
                            // Bandingkan waktu review untuk menentukan status terbaru
                            $latestTime = null;
                            $latestStatus = $existingProgress->status;

                            if ($guestProgress->last_reviewed_at) {
                                if (!$existingProgress->last_reviewed_at || $guestProgress->last_reviewed_at->gt($existingProgress->last_reviewed_at)) {
                                    $latestTime = $guestProgress->last_reviewed_at;
                                    $latestStatus = $guestProgress->status;
                                } else {
                                    $latestTime = $existingProgress->last_reviewed_at;
                                }
                            } else {
                                $latestTime = $existingProgress->last_reviewed_at;
                            }

                            $existingProgress->update([
                                'status' => $latestStatus,
                                'last_reviewed_at' => $latestTime,
                                'review_count' => $existingProgress->review_count + $guestProgress->review_count,
                            ]);
                            $guestProgress->delete();
                        } else {
                            $guestProgress->update(['user_id' => $user->id]);
                        }
                    }

                    // 2. Update review_logs
                    ReviewLog::where('user_id', $guestUser->id)->update(['user_id' => $user->id]);

                    // 3. Update memorization_sessions
                    MemorizationSession::where('user_id', $guestUser->id)->update(['user_id' => $user->id]);

                    // Hapus user guest untuk bersih-bersih database
                    $guestUser->delete();
                }
            }

            // Buat personal access token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Redirect kembali ke halaman frontend Nuxt
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect($frontendUrl . '/auth/callback?token=' . $token);

        } catch (\Exception $e) {
            logger()->error('Google OAuth Error: ' . $e->getMessage());
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect($frontendUrl . '/profile?error=auth_failed');
        }
    }

    /**
     * GET /api/auth/me — Get details of current authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'is_guest' => str_contains($user->email, 'guest_'),
            ]
        ]);
    }
}
