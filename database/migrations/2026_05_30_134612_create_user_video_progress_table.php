<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('watched_percent')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_video_progress');
    }
};
