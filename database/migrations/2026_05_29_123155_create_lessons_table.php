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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('level')->nullable();
            $table->timestamps();
        });

        Schema::create('lesson_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->unique(['lesson_id', 'locale']);

            $table->string('name');
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_translations');
        Schema::dropIfExists('lessons');
    }
};
