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
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('redlight_paid_at')->nullable()->after('credit_deducted_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('redlight_paid_at');
        });
    }
};
