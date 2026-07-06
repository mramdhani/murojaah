<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mushaf_words', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('word_id')->unique();    // Global ID dari Quran.com
            $table->string('verse_key', 10);                 // e.g. "2:255"
            $table->unsignedTinyInteger('surah_number');     // 1–114
            $table->unsignedSmallInteger('ayah_number');     // ayat ke-n
            $table->unsignedTinyInteger('position');         // posisi kata dalam ayat
            $table->unsignedSmallInteger('page_number');     // 1–604
            $table->unsignedTinyInteger('line_number');      // 1–15
            $table->enum('char_type', ['word', 'end']);      // word = kata, end = nomor ayat
            $table->string('code_v2', 8);                   // karakter PUA font QCF V2

            $table->index('page_number');
            $table->index('verse_key');
            $table->index(['surah_number', 'ayah_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mushaf_words');
    }
};
