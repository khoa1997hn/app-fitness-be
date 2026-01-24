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
        Schema::create('google_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purchase_token');
            $table->string('order_id')->nullable();
            $table->string('item_id');
            $table->timestamp('transaction_date')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('status', 20)->nullable();
            $table->timestamps();

            $table->index('subscription_id');
            $table->index('user_id');
            $table->index('purchase_token');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_subscriptions');
    }
};
