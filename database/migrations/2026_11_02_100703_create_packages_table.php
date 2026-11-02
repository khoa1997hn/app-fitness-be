<?php

use App\Share\Enums\PackageStatus;
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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('package_type', 20);
            $table->decimal('price', 12, 0);
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedSmallInteger('class_credits')->nullable();
            $table->unsignedSmallInteger('session_duration_minutes');
            $table->boolean('is_off_peak')->default(false);
            $table->unsignedSmallInteger('base_people_max')->default(2);
            $table->unsignedSmallInteger('extra_people_session_max')->default(100);
            $table->string('status', 20)->default(PackageStatus::Active)->index();
            $table->timestamps();
        });

        Schema::create('package_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->unique(['package_id', 'locale']);

            $table->string('name');
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_translations');
        Schema::dropIfExists('packages');
    }
};
