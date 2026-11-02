<?php

use App\Share\Enums\Role;

/*
|--------------------------------------------------------------------------
| Ma trận quyền API hệ thống phòng tập
|--------------------------------------------------------------------------
|
| Key: <nhóm>.<hành động>, dùng qua middleware `permission:<nhóm>.<hành động>`.
| Deny by default: không khai báo = không ai được.
| Contract đầy đủ: fitness-web/docs/auth/permissions.md — thêm key khi API của màn đó ra đời.
|
*/

return [
    'packages' => [
        'manage' => [Role::Admin],
    ],
    'users' => [
        'manage' => [Role::Admin, Role::Receptionist, Role::Trainer],
    ],
    'orders' => [
        'create' => [Role::Admin, Role::Receptionist, Role::Trainer],
    ],
    'payments' => [
        'manage' => [Role::Admin, Role::Receptionist, Role::Trainer],
    ],
    'schedules' => [
        'timetable' => [
            'manage' => [Role::Admin],
        ],
    ],
    'bookings' => [
        'manage' => [Role::Admin, Role::Receptionist],
        'cancel_class' => [Role::Admin],
        'private' => [
            'approve' => [Role::Admin],
            'edit_form' => [Role::Admin],
        ],
    ],
    'student' => [
        'portal' => [Role::Member],
    ],
];
