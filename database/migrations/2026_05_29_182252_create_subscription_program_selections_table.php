<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_program_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['subscription_id', 'program_id'], 'sub_prog_selection_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_program_selections');
    }
};
