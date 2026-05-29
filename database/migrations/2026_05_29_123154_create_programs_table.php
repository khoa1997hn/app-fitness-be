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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->decimal('rating', 2, 1)->nullable();
            $table->timestamps();
        });

        Schema::create('program_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->unique(['program_id', 'locale']);

            $table->string('name');
            $table->text('description')->nullable();
            $table->jsonb('cover')->nullable();
            $table->integer('sort')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_translations');
        Schema::dropIfExists('programs');
    }
};
