<?php

namespace Tests\Feature;

use App\Share\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class RegistrationBackfillTest extends TestCase
{
    use DatabaseTruncation;

    public function test_cutoff_backfill_preserves_records_timestamps_and_includes_soft_deleted(): void
    {
        $old = User::factory()->create(['created_at' => '2026-11-01 23:59:59', 'updated_at' => '2026-11-01 23:59:59']);
        $boundary = User::factory()->create(['created_at' => '2026-11-02 00:00:00', 'updated_at' => '2026-11-02 12:00:00']);
        $deleted = User::factory()->create(['created_at' => '2026-10-20 12:00:00', 'updated_at' => '2026-10-21 12:00:00', 'deleted_at' => '2026-10-21 12:00:00']);
        $migration = require database_path('migrations/2026_11_03_080532_add_registration_provenance_to_users_table.php');
        $migration->down();
        $migration->up();
        $this->assertSame(3, User::withTrashed()->count());
        $this->assertSame('app', $old->fresh()->registration_source->value);
        $this->assertSame('web', $boundary->fresh()->registration_source->value);
        $this->assertSame('app', $deleted->fresh()->registration_source->value);
        foreach ([$old, $boundary, $deleted] as $user) {
            $fresh = $user->fresh();
            $this->assertEquals($user->created_at, $fresh->created_at);
            $this->assertEquals($user->updated_at, $fresh->updated_at);
            $this->assertEquals($user->deleted_at, $fresh->deleted_at);
            $this->assertNull($fresh->created_by);
        }
    }
}
