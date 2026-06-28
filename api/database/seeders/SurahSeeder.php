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
        $this->command->info('✅ ' . count($surahs) . ' surahs seeded successfully.');
    }
}
