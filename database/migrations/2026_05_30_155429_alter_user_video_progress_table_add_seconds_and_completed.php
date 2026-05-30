<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_video_progress', function (Blueprint $table) {
            $table->dropColumn('watched_percent');
            $table->unsignedInteger('watched_seconds')->default(0)->after('video_id');
            $table->boolean('is_completed')->default(false)->after('watched_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('user_video_progress', function (Blueprint $table) {
            $table->dropColumn(['watched_seconds', 'is_completed']);
            $table->unsignedTinyInteger('watched_percent')->default(0)->after('video_id');
        });
    }
};
