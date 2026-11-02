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
        Schema::create('user_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('package_type', 20);
            $table->unsignedSmallInteger('total_credits')->nullable();
            $table->unsignedSmallInteger('remaining_credits')->nullable();
            $table->unsignedSmallInteger('session_duration_minutes');
            $table->boolean('is_off_peak');
            $table->unsignedSmallInteger('base_people_max');
            $table->unsignedSmallInteger('extra_people_session_max');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_packages');
    }
};
