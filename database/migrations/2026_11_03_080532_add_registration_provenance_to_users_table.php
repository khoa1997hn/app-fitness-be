<?php

use App\Share\Enums\RegistrationSource;
use App\Share\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_source')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // User-confirmed historical cutoff; query updates preserve created_at/updated_at.
        $model = new User;
        $model->timestamps = false;
        $model->newQuery()->withTrashed()->whereNull('registration_source')
            ->where('created_at', '<', '2026-11-02 00:00:00')->update(['registration_source' => RegistrationSource::App]);
        $model->newQuery()->withTrashed()->whereNull('registration_source')
            ->where('created_at', '>=', '2026-11-02 00:00:00')->update(['registration_source' => RegistrationSource::Web]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('registration_source');
        });
    }
};
