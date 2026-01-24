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
        Schema::create('apple_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_transaction_id');
            $table->string('transaction_id');
            $table->string('product_id');
            $table->timestamp('purchase_date')->nullable();
            $table->timestamp('expires_date')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('status', 20)->nullable();
            $table->timestamps();

            $table->index('subscription_id');
            $table->index('user_id');
            $table->index('original_transaction_id');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apple_subscriptions');
    }
};
