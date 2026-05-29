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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('video_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->unique(['video_id', 'locale']);

            $table->jsonb('file');
            $table->unsignedInteger('duration_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_translations');
        Schema::dropIfExists('videos');
    }
};
