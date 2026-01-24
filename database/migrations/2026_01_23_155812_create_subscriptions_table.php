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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('plan', 20);
            $table->string('provider', 20);
            $table->string('status', 20);
            $table->string('provider_subscription_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('grace_period_ends_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('billing_cycle', 20)->default('monthly');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('provider_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
