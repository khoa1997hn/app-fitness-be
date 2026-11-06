<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redlight chuyển từ trả tiền theo buổi sang mua lượt trước, dùng buổi nào trừ lượt buổi đó.
 * Chưa có dữ liệu thật nên bỏ hẳn cột của luồng cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('redlight_refunded_by');
            $table->dropColumn(['redlight_paid_at', 'redlight_refunded_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('has_redlight')->default(false)->after('credit_deducted_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('has_redlight');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('redlight_paid_at')->nullable()->after('credit_deducted_at');
            $table->timestamp('redlight_refunded_at')->nullable()->after('redlight_paid_at');
            $table->foreignId('redlight_refunded_by')->nullable()->after('redlight_refunded_at')->constrained('users')->nullOnDelete();
        });
    }
};
