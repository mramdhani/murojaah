<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('surah_id')->constrained('surahs')->onDelete('cascade');
            $table->foreignId('ayah_id')->constrained('ayahs')->onDelete('cascade');
            $table->enum('status', ['forgot', 'doubtful', 'fluent']);
            $table->boolean('was_revealed')->default(false);
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(['user_id', 'reviewed_at']);
            $table->index(['user_id', 'surah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_logs');
    }
};
