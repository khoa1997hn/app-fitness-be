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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->unsignedInteger('used_quantity')->default(0);
            $table->string('discount_type'); // percentage or fixed_amount
            $table->decimal('discount_value', 10, 2);
            $table->foreignId('creator_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updater_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
