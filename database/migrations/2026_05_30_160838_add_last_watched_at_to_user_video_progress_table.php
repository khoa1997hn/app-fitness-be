<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_video_progress', function (Blueprint $table) {
            $table->timestamp('last_watched_at')->nullable()->after('is_completed');
        });
    }

    public function down(): void
    {
        Schema::table('user_video_progress', function (Blueprint $table) {
            $table->dropColumn('last_watched_at');
        });
    }
};
