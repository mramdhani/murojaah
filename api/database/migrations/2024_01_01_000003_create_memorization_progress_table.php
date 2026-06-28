<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memorization_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('surah_id')->constrained('surahs')->onDelete('cascade');
            $table->foreignId('ayah_id')->constrained('ayahs')->onDelete('cascade');
            $table->enum('status', ['unreviewed', 'forgot', 'doubtful', 'fluent'])->default('unreviewed');
            $table->timestamp('last_reviewed_at')->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'surah_id', 'ayah_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memorization_progress');
    }
};
