<?php

namespace Database\Seeders;

use App\Models\Surah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class SurahSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Fetching surahs from Al Quran Cloud API...');

        $response = Http::timeout(30)->get('https://api.alquran.cloud/v1/surah');

        if (!$response->successful()) {
            $this->command->error('Failed to fetch surahs from API. Status: ' . $response->status());
            return;
        }

        $surahs = $response->json('data');

        if (empty($surahs)) {
            $this->command->error('No surah data received from API.');
            return;
        }

        $bar = $this->command->getOutput()->createProgressBar(count($surahs));
        $bar->start();

        foreach ($surahs as $surah) {
            Surah::updateOrCreate(
                ['number' => $surah['number']],
                [
                    'name_arabic' => $surah['name'],
                    'name_latin' => $surah['englishName'],
                    'name_translation' => $surah['englishNameTranslation'],
                    'total_ayah' => $surah['numberOfAyahs'],
                    'revelation_place' => strtolower($surah['revelationType']), // Meccan or Medinan
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();

        // Secondary step: Translate Latin names and translations to Indonesian
        $this->command->info('Translating surah names and translations to Indonesian...');
        try {
            $indoResponse = Http::timeout(30)->get('https://equran.id/api/v2/surat');
            if ($indoResponse->successful()) {
                $indoSurahs = $indoResponse->json('data');
                foreach ($indoSurahs as $indoSurah) {
                    Surah::where('number', $indoSurah['nomor'])->update([
                        'name_latin' => $indoSurah['namaLatin'],
                        'name_translation' => $indoSurah['arti'],
                    ]);
                }
                $this->command->info('✅ Translated 114 surah names to Indonesian successfully.');
            } else {
                $this->command->warn('Could not translate names: equran.id returned status ' . $indoResponse->status());
            }
        } catch (\Exception $e) {
            $this->command->warn('Could not translate names: ' . $e->getMessage());
        }

        $this->command->info('✅ Seeders completed.');
    }
}
