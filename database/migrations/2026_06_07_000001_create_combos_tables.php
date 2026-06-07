<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('combo_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->unique(['combo_id', 'locale']);
            $table->string('name');
            $table->jsonb('cover')->nullable();
        });

        Schema::create('combo_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->unique(['combo_id', 'program_id']);
        });

        Schema::create('combo_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort');
            $table->jsonb('icon');
            $table->unique(['combo_id', 'sort']);
        });

        Schema::create('combo_info_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_info_id')->constrained('combo_infos')->cascadeOnDelete();
            $table->string('locale')->index();
            $table->unique(['combo_info_id', 'locale']);
            $table->string('text', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_info_translations');
        Schema::dropIfExists('combo_infos');
        Schema::dropIfExists('combo_program');
        Schema::dropIfExists('combo_translations');
        Schema::dropIfExists('combos');
    }
};
