<?php

namespace Database\Seeders;

use App\Models\MushafWord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MushafWordSeeder extends Seeder
{
    /**
     * Download all 604 pages of QCF V2 word data from Quran.com API
     * and store them in the mushaf_words table.
     *
     * Estimated time: ~3–5 minutes (204 pages with 200ms delay between requests)
     */
    public function run(): void
    {
        $this->command->info('🕌 Starting Mushaf QCF V2 word seeder...');
        $this->command->info('   Downloading 604 pages from Quran.com API...');

        // Clear existing data
        DB::table('mushaf_words')->truncate();

        $totalWords = 0;
        $failedPages = [];

        for ($page = 1; $page <= 604; $page++) {
            $success = $this->seedPage($page, $totalWords);

            if (! $success) {
                $failedPages[] = $page;
            }

            // Progress indicator every 10 pages
            if ($page % 10 === 0 || $page === 604) {
                $this->command->info("   ✓ Page {$page}/604 — Total words so far: {$totalWords}");
            }

            // Polite delay to avoid rate limiting
            if ($page < 604) {
                usleep(200_000); // 200ms
            }
        }

        $this->command->info("\n✅ Done! Total words stored: {$totalWords}");

        if (! empty($failedPages)) {
            $this->command->warn('⚠️  Failed pages (will retry): ' . implode(', ', $failedPages));
            $this->command->info('   Retrying failed pages...');

            foreach ($failedPages as $page) {
                sleep(2); // Longer delay for retry
                $this->seedPage($page, $totalWords);
            }
        }

        $this->command->info('🎉 Mushaf QCF V2 data seeding complete!');
    }

    private function seedPage(int $page, int &$totalWords): bool
    {
        try {
            $url = "https://api.quran.com/api/v4/verses/by_page/{$page}";
            $response = Http::timeout(30)->get($url, [
                'words'       => 'true',
                'word_fields' => 'code_v2,line_number,char_type_name',
                'mushaf'      => 1,
                'per_page'    => 50,
            ]);

            if (! $response->successful()) {
                $this->command->warn("   ⚠ Page {$page} returned HTTP " . $response->status());
                return false;
            }

            $jsonString = $response->body();
            // Apply regex to rename/remove the duplicate line_number key so the first one (V1) is preserved
            $jsonString = preg_replace(
                '/"line_number"\s*:\s*(\d+)\s*,\s*"page_number"\s*:\s*(\d+)\s*,\s*"line_number"\s*:\s*(\d+)/',
                '"line_number":$1,"page_number":$2,"line_number_v2":$3',
                $jsonString
            );
            $data = json_decode($jsonString, true);
            $verses = $data['verses'] ?? [];
            $batch = [];

            foreach ($verses as $verse) {
                $parts    = explode(':', $verse['verse_key']);
                $surahNum = (int) $parts[0];
                $ayahNum  = (int) $parts[1];
                $words    = $verse['words'] ?? [];

                foreach ($words as $word) {
                    $charType = $word['char_type_name'] ?? 'word';

                    // Only store 'word' and 'end' types; skip 'pause' marks etc.
                    if (! in_array($charType, ['word', 'end'])) {
                        continue;
                    }

                    $codeV2 = $word['code_v2'] ?? $word['text'] ?? '';

                    // Skip if no code_v2 available
                    if (empty($codeV2)) {
                        continue;
                    }

                    $pageOverrides = [
                        90  => array_fill_keys(range(19, 20), 594),
                        92  => array_fill_keys(range(10, 14), 595),
                        94  => array_fill_keys(range(3, 8), 596),
                        96  => array_fill_keys(range(13, 19), 597),
                        98  => array_fill_keys(range(6, 7), 598),
                        100 => array_fill_keys(range(10, 11), 599),
                    ];

                    $lineOverrides = [
                        90  => [19 => 14, 20 => 15],
                        92  => [10 => 14, 11 => 14, 12 => 14, 13 => 15, 14 => 15],
                        94  => [3 => 14, 4 => 14, 5 => 14, 6 => 15, 7 => 15, 8 => 15],
                        100 => [10 => 15, 11 => 15],
                    ];

                    $targetPage = $page;
                    if (isset($pageOverrides[$surahNum][$ayahNum])) {
                        $targetPage = $pageOverrides[$surahNum][$ayahNum];
                    }

                    $targetLine = (int) ($word['line_number'] ?? 0);
                    if (isset($lineOverrides[$surahNum][$ayahNum])) {
                        $targetLine = $lineOverrides[$surahNum][$ayahNum];
                    } elseif ($targetPage === 600 && in_array($surahNum, [101, 102])) {
                        $targetLine = max(1, $targetLine - 1);
                    }

                    $batch[] = [
                        'word_id'      => (int) $word['id'],
                        'verse_key'    => $verse['verse_key'],
                        'surah_number' => $surahNum,
                        'ayah_number'  => $ayahNum,
                        'position'     => (int) $word['position'],
                        'page_number'  => $targetPage,
                        'font_page'    => (int) ($word['page_number'] ?? $page),
                        'line_number'  => $targetLine,
                        'char_type'    => $charType,
                        'code_v2'      => $codeV2,
                    ];
                }
            }

            if (! empty($batch)) {
                // Upsert to handle reruns gracefully
                MushafWord::upsert(
                    $batch,
                    ['word_id'],
                    ['verse_key', 'surah_number', 'ayah_number', 'position', 'page_number', 'font_page', 'line_number', 'char_type', 'code_v2']
                );
                $totalWords += count($batch);
            }

            return true;
        } catch (\Exception $e) {
            $this->command->warn("   ✗ Page {$page} failed: " . $e->getMessage());
            return false;
        }
    }
}
