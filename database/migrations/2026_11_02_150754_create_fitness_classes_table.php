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
        Schema::create('fitness_classes', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('category', 30);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('fitness_class_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fitness_class_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->unique(['fitness_class_id', 'locale']);

            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fitness_class_translations');
        Schema::dropIfExists('fitness_classes');
    }
};
