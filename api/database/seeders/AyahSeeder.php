<?php

namespace Database\Seeders;

use App\Models\Ayah;
use App\Models\Surah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class AyahSeeder extends Seeder
{
    public function run(): void
    {
        $surahs = Surah::orderBy('number')->get();

        if ($surahs->isEmpty()) {
            $this->command->error('No surahs found. Run SurahSeeder first.');
            return;
        }

        $this->command->info('Fetching complete Quran (Arabic Uthmani)...');
        $arabicResponse = Http::timeout(60)->get('https://api.alquran.cloud/v1/quran/quran-uthmani');

        if (!$arabicResponse->successful()) {
            $this->command->error('Failed to fetch Arabic text. Status: ' . $arabicResponse->status());
            return;
        }

        $this->command->info('Fetching Indonesian translation...');
        $translationResponse = Http::timeout(60)->get('https://api.alquran.cloud/v1/quran/id.indonesian');

        if (!$translationResponse->successful()) {
            $this->command->error('Failed to fetch translation. Status: ' . $translationResponse->status());
            return;
        }

        $arabicSurahs = $arabicResponse->json('data.surahs');
        $translationSurahs = $translationResponse->json('data.surahs');

        // Build translation lookup: surah_number -> ayah_number -> text
        $translationMap = [];
        foreach ($translationSurahs as $surah) {
            foreach ($surah['ayahs'] as $ayah) {
                $translationMap[$surah['number']][$ayah['numberInSurah']] = $ayah['text'];
            }
        }

        $bar = $this->command->getOutput()->createProgressBar(count($arabicSurahs));
        $bar->start();

        $totalAyahs = 0;

        foreach ($arabicSurahs as $arabicSurah) {
            $surah = $surahs->firstWhere('number', $arabicSurah['number']);

            if (!$surah) {
                $bar->advance();
                continue;
            }

            foreach ($arabicSurah['ayahs'] as $ayah) {
                $ayahNumber = $ayah['numberInSurah'];
                $translation = $translationMap[$arabicSurah['number']][$ayahNumber] ?? null;
                $textArabic = $ayah['text'];

                // Strip Bismillah prefix from first ayah of all surahs except Al-Fatihah (number 1)
                if ($arabicSurah['number'] > 1 && $ayahNumber === 1) {
                    $bismillahPrefix = 'بِسْمِ ٱللَّهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ ';
                    if (str_starts_with($textArabic, $bismillahPrefix)) {
                        $textArabic = substr($textArabic, strlen($bismillahPrefix));
                    }
                }

                Ayah::updateOrCreate(
                    [
                        'surah_id' => $surah->id,
                        'ayah_number' => $ayahNumber,
                    ],
                    [
                        'text_arabic' => $textArabic,
                        'translation_id' => $translation,
                        'juz' => $ayah['juz'] ?? null,
                        'page' => $ayah['page'] ?? null,
                    ]
                );
                $totalAyahs++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("✅ {$totalAyahs} ayahs seeded successfully (Arabic + Indonesian translation).");
    }
}
