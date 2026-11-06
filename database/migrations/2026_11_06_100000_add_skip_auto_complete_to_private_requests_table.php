<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_requests', function (Blueprint $table) {
            $table->boolean('skip_auto_complete')->default(false)->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('private_requests', function (Blueprint $table) {
            $table->dropColumn('skip_auto_complete');
        });
    }
};
