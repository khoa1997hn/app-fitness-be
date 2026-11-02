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
        Schema::create('therapy_services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->decimal('single_price', 12, 0);
            $table->unsignedSmallInteger('single_duration_minutes');
            $table->decimal('combo5_price', 12, 0);
            $table->timestamps();
        });

        Schema::create('therapy_service_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('therapy_service_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->unique(['therapy_service_id', 'locale']);

            $table->string('name');
            $table->text('long_description')->nullable();
            $table->json('benefits')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('therapy_service_translations');
        Schema::dropIfExists('therapy_services');
    }
};
