<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ayah;
use App\Models\MemorizationProgress;
use App\Models\MushafWord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AyahController extends Controller
{
    /**
     * GET /api/surahs/{surahId}/ayahs — Get all ayahs for a surah
     */
    public function index(Request $request, int $surahId): JsonResponse
    {
        $userId = $request->user()?->id;

        $ayahs = Ayah::where('surah_id', $surahId)
            ->orderBy('ayah_number')
            ->get();

        // Get progress for all ayahs in this surah
        $progressMap = $userId
            ? MemorizationProgress::where('user_id', $userId)
                ->where('surah_id', $surahId)
                ->get()
                ->keyBy('ayah_id')
            : collect();

        $data = $ayahs->map(function ($ayah) use ($progressMap) {
            $progress = $progressMap->get($ayah->id);

            return [
                'id' => $ayah->id,
                'surah_id' => $ayah->surah_id,
                'ayah_number' => $ayah->ayah_number,
                'text_arabic' => $ayah->text_arabic,
                'translation_id' => $ayah->translation_id,
                'juz' => $ayah->juz,
                'page' => $ayah->page,
                'progress_status' => $progress ? $progress->status : 'unreviewed',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/mushaf/pages/{pageNumber} - Get a Mushaf page with QCF V2 line data.
     */
    public function byPage(Request $request, int $pageNumber): JsonResponse
    {
        abort_unless($pageNumber >= 1 && $pageNumber <= 604, 404);

        $ayahs = Ayah::with('surah')
            ->where('page', $pageNumber)
            ->orderBy('id')
            ->get();

        abort_if($ayahs->isEmpty(), 404);

        $progressMap = $request->user()
            ? MemorizationProgress::where('user_id', $request->user()->id)
                ->whereIn('ayah_id', $ayahs->pluck('id'))
                ->get()
                ->keyBy('ayah_id')
            : collect();

        // --- QCF V2 word/line data ---
        // Order by word_id to preserve the canonical Quran.com word order.
        // Sorting by surah_number/ayah_number would group all verses of a
        // surah together even when they span non-contiguous lines (e.g.
        // page 595 has Surah Al-Layl ayahs 10-14 on lines 1-2 and ayahs
        // 1-9 on lines 13-15), breaking the physical 15-line layout.
        $mushafWords = MushafWord::where('page_number', $pageNumber)
            ->orderBy('word_id')
            ->get();

        // Group words by line_number → array of lines 1..15
        $linesMap = [];
        foreach ($mushafWords as $word) {
            $linesMap[$word->line_number][] = [
                'word_id'     => $word->word_id,
                'verse_key'   => $word->verse_key,
                'position'    => $word->position,
                'char_type'   => $word->char_type,
                'code_v2'     => $word->code_v2,
                'page_number' => $word->font_page ?? $word->page_number,
            ];
        }

        // Sort lines by line_number ascending to ensure correct physical page order (from top to bottom).
        ksort($linesMap);

        // Build a verse_key → progress map for word-level highlight
        $verseProgressMap = [];
        $ayahByVerseKey = [];
        $tajweedWordsByVerseKey = [];
        foreach ($ayahs as $ayah) {
            $progress = $progressMap->get($ayah->id);
            $key = $ayah->surah->number . ':' . $ayah->ayah_number;
            $verseProgressMap[$key] = $progress ? $progress->status : 'unreviewed';
            $ayahByVerseKey[$key] = $ayah;
            $tajweedWordsByVerseKey[$key] = preg_split('/\s+/u', trim($ayah->text_arabic ?? '')) ?: [];
        }

        // Attach the stored tajweed-marked Arabic word to each QCF word. This
        // keeps the canonical 15-line pagination while allowing letter-level
        // tajweed colours in the client. End-of-ayah glyphs are rendered by
        // the client as their own ornamental marker.
        foreach ($linesMap as &$lineWords) {
            foreach ($lineWords as &$lineWord) {
                $wordIndex = max(0, ((int) $lineWord['position']) - 1);
                $lineWord['text_tajweed'] = $lineWord['char_type'] === 'word'
                    ? ($tajweedWordsByVerseKey[$lineWord['verse_key']][$wordIndex] ?? null)
                    : null;
            }
            unset($lineWord);
        }
        unset($lineWords);

        // With the font_page database column now successfully migrated and seeded,
        // page fonts are isolated properly so glyph collisions are solved at the database level.
        $unicodeFallbackLines = [];

        // Build surah boundaries: which line does each surah start on?
        // Some surahs have a wrapped layout where they start at a high line
        // number (e.g. 15) and continue at low line numbers (e.g. 1-2).
        // In that case, the surah banner should appear at the continuation
        // line (the lowest line number for that surah), not at the physical
        // start, so the user sees the banner before reading the surah.
        $surahBoundaries = [];
        $surahLineRanges = []; // surah_num => [min_line, max_line]
        foreach ($mushafWords as $word) {
            $surahNum = $word->surah_number;
            if (!isset($surahLineRanges[$surahNum])) {
                $surahLineRanges[$surahNum] = ['min' => $word->line_number, 'max' => $word->line_number];
            } else {
                $surahLineRanges[$surahNum]['min'] = min($surahLineRanges[$surahNum]['min'], $word->line_number);
                $surahLineRanges[$surahNum]['max'] = max($surahLineRanges[$surahNum]['max'], $word->line_number);
            }
            if ($word->ayah_number === 1 && $word->position === 1) {
                $surahBoundaries[$surahNum] = $word->line_number;
            }
        }

        // For wrapped surahs (where the first ayah appears at a high line
        // number but the surah also has words at lower line numbers), adjust
        // the boundary to the lowest line number so the banner appears at
        // the reading start rather than the physical start.
        foreach ($surahBoundaries as $surahNum => $startLine) {
            $range = $surahLineRanges[$surahNum] ?? null;
            if ($range && $startLine > $range['min']) {
                // This surah is wrapped: first ayah is at a high line,
                // but the surah continues at lower lines. Move the
                // boundary to the lowest line so the banner appears
                // at the reading start.
                $surahBoundaries[$surahNum] = $range['min'];
            }
        }

        return response()->json([
            'data' => [
                'page_number'      => $pageNumber,
                'juz'              => $ayahs->pluck('juz')->filter()->unique()->values(),
                'surahs'           => $ayahs->map(fn ($ayah) => [
                    'id'               => $ayah->surah->id,
                    'number'           => $ayah->surah->number,
                    'name_latin'       => $ayah->surah->name_latin,
                    'name_arabic'      => $ayah->surah->name_arabic,
                    'revelation_place' => $ayah->surah->revelation_place,
                    'total_ayah'       => $ayah->surah->total_ayah,
                    'starts_at_line'   => $surahBoundaries[$ayah->surah->number] ?? null,
                ])->unique('id')->values(),
                'lines'            => collect($linesMap)->map(function ($words, $lineNum) use ($unicodeFallbackLines, $ayahByVerseKey) {
                    $fallbackVerses = [];
                    if (isset($unicodeFallbackLines[$lineNum])) {
                        foreach (collect($words)->pluck('verse_key')->unique()->values() as $verseKey) {
                            $ayah = $ayahByVerseKey[$verseKey] ?? null;
                            if ($ayah) {
                                $fallbackVerses[] = [
                                    'verse_key' => $verseKey,
                                    'ayah_number' => $ayah->ayah_number,
                                    'text' => $this->cleanTajweedText($ayah->text_arabic),
                                ];
                            }
                        }
                    }

                    return [
                        'line_number' => (int) $lineNum,
                        'words' => $words,
                        'unicode_fallback' => $fallbackVerses,
                    ];
                })->values(),
                'verse_progress'   => $verseProgressMap,
                'ayahs'            => $ayahs->map(function ($ayah) use ($progressMap) {
                    $progress = $progressMap->get($ayah->id);
                    return [
                        'id'              => $ayah->id,
                        'surah_id'        => $ayah->surah_id,
                        'verse_key'       => $ayah->surah->number . ':' . $ayah->ayah_number,
                        'ayah_number'     => $ayah->ayah_number,
                        'juz'             => $ayah->juz,
                        'page'            => $ayah->page,
                        'progress_status' => $progress ? $progress->status : 'unreviewed',
                        'text_arabic'      => $this->cleanTajweedText($ayah->text_arabic),
                        'translation_id'  => $ayah->translation_id,
                    ];
                })->values(),
            ],
        ]);
    }

    private function cleanTajweedText(?string $text): string
    {
        $withoutOpeningTags = preg_replace('/\[[a-z](?::\d+)?\[/', '', $text ?? '');

        return trim(str_replace(']', '', $withoutOpeningTags ?? ''));
    }


    /**
     * GET /api/surahs/{surahId}/ayahs/{ayahNumber} — Get single ayah
     */
    public function show(Request $request, int $surahId, int $ayahNumber): JsonResponse
    {
        $userId = $request->user()?->id;

        $ayah = Ayah::where('surah_id', $surahId)
            ->where('ayah_number', $ayahNumber)
            ->firstOrFail();

        $surah = $ayah->surah;

        $progress = $userId
            ? MemorizationProgress::where('user_id', $userId)
                ->where('ayah_id', $ayah->id)
                ->first()
            : null;

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
                'juz' => $ayah->juz,
                'page' => $ayah->page,
                'progress_status' => $progress ? $progress->status : 'unreviewed',
            ],
        ]);
    }
}
