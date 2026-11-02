<?php

namespace Database\Seeders;

use App\Share\Enums\Role;
use App\Share\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tài khoản demo cho từng role hệ thống phòng tập. KHÔNG dùng trên production.
 * Email và họ tên khớp mock `fitness-web/src/mocks/systemUsersStore.ts` để các màn chưa nối API vẫn có dữ liệu.
 */
class GymDemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['admin@demo.kore.vn', 'Nguyễn Văn', 'Admin', Role::Admin],
            ['receptionist@demo.kore.vn', 'Trần Thị', 'Lễ tân', Role::Receptionist],
            ['trainer@demo.kore.vn', 'Nguyễn Thu', 'Hà', Role::Trainer],
            ['member@demo.kore.vn', 'Phạm Thị', 'Member', Role::Member],
            ['hlvtrainee@demo.kore.vn', 'Trần Minh', 'HLV', Role::Member],
        ];

        foreach ($users as [$email, $firstName, $lastName, $role]) {
            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => 'Demo@123',
                    'role' => $role,
                ]
            );
        }
    }
}
