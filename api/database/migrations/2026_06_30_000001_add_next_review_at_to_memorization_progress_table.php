<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('memorization_progress', function (Blueprint $table) {
            $table->timestamp('next_review_at')->nullable()->after('last_reviewed_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memorization_progress', function (Blueprint $table) {
            $table->dropColumn('next_review_at');
        });
    }
};
