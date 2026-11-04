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
            $table->timestamp('redlight_refunded_at')->nullable()->after('redlight_paid_at');
            $table->foreignId('redlight_refunded_by')->nullable()->after('redlight_refunded_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('redlight_refunded_by');
            $table->dropColumn('redlight_refunded_at');
        });
    }
};
