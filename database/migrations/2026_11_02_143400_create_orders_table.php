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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);
            $table->foreignId('package_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('therapy_service_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('therapy_variant', 20)->nullable();
            $table->string('name');
            $table->decimal('amount', 12, 0);
            $table->string('status', 20)->index();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_phone', 20)->nullable();
            $table->string('buyer_email')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->longText('terms_signature')->nullable();
            $table->text('note')->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->string('transaction_code', 30)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
