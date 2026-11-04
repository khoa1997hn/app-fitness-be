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
    'app' => [
        'manage' => [Role::Admin],
    ],
    'packages' => [
        'manage' => [Role::Admin],
    ],
    'users' => [
        'manage' => [Role::Admin, Role::Receptionist, Role::Trainer],
        'change_role' => [Role::Admin],
        'extend_package' => [Role::Admin],
    ],
    'records' => [
        'delete' => [Role::Admin],
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
        'teaching' => [
            'view' => [Role::Admin, Role::Receptionist, Role::Trainer],
            'cancel' => [Role::Admin],
        ],
        'cancel_request' => [
            'create' => [Role::Trainer],
            'review' => [Role::Admin],
        ],
    ],
    'attendance' => [
        'view' => [Role::Admin, Role::Receptionist, Role::Trainer],
        'mark' => [Role::Admin, Role::Receptionist, Role::Trainer],
    ],
    'bookings' => [
        'manage' => [Role::Admin, Role::Receptionist],
        'cancel_class' => [Role::Admin],
        'redlight_refund' => [Role::Admin],
        'private' => [
            'approve' => [Role::Admin],
            'edit_form' => [Role::Admin],
        ],
    ],
    'events' => [
        'manage' => [Role::Admin],
    ],
    'reports' => [
        'view' => [Role::Admin],
    ],
    'dashboard' => [
        'view' => [Role::Admin, Role::Receptionist, Role::Trainer],
    ],
    'student' => [
        'portal' => [Role::Member],
    ],
];
