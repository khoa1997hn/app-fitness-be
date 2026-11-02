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
        Schema::create('private_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_package_id')->constrained()->restrictOnDelete();
            $table->string('discipline', 20);
            $table->dateTime('requested_at');
            $table->unsignedSmallInteger('people_count');
            $table->unsignedSmallInteger('extra_people_count')->default(0);
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->string('status', 20)->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('private_requests');
    }
};
