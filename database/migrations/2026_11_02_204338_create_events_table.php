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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->dateTime('event_date')->index();
            $table->string('location');
            $table->decimal('ticket_price', 12, 0)->default(0);
            $table->unsignedInteger('total_tickets');
            $table->string('status', 20)->default('open')->index();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('event_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->unique(['event_id', 'locale']);

            $table->string('name');
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_translations');
        Schema::dropIfExists('events');
    }
};
