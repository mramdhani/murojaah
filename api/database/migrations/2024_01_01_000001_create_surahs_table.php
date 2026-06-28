<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surahs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('number')->unique();
            $table->string('name_arabic', 100);
            $table->string('name_latin', 100);
            $table->string('name_translation', 255)->nullable();
            $table->unsignedSmallInteger('total_ayah');
            $table->enum('revelation_place', ['meccan', 'medinan']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surahs');
    }
};
