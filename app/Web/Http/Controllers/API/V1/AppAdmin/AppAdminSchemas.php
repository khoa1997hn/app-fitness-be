<?php

namespace App\Web\Http\Controllers\API\V1\AppAdmin;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'AppAdminFile', type: 'object', properties: [
    new OA\Property(property: 'path', type: 'string'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'extension', type: 'string', nullable: true),
    new OA\Property(property: 'size', type: 'integer', nullable: true),
    new OA\Property(property: 'url', type: 'string'),
])]
#[OA\Schema(schema: 'AppAdminProgramTranslation', type: 'object', properties: [
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'cover', ref: '#/components/schemas/AppAdminFile', nullable: true),
    new OA\Property(property: 'sort', type: 'integer'),
])]
#[OA\Schema(schema: 'AppAdminLessonTranslation', type: 'object', properties: [
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'teacher_name', type: 'string', nullable: true),
    new OA\Property(property: 'thumbnail', ref: '#/components/schemas/AppAdminFile', nullable: true),
])]
#[OA\Schema(schema: 'AppAdminBannerTranslation', type: 'object', properties: [
    new OA\Property(property: 'image', ref: '#/components/schemas/AppAdminFile', nullable: true),
    new OA\Property(property: 'link_url', type: 'string', nullable: true),
    new OA\Property(property: 'order', type: 'integer'),
    new OA\Property(property: 'is_active', type: 'boolean'),
])]
#[OA\Schema(schema: 'AppAdminComboTranslation', type: 'object', properties: [
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'cover', ref: '#/components/schemas/AppAdminFile', nullable: true),
])]
#[OA\Schema(schema: 'AppAdminInfoTranslation', type: 'object', properties: [
    new OA\Property(property: 'text', type: 'string'),
])]
#[OA\Schema(schema: 'AppAdminVideo', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'file', ref: '#/components/schemas/AppAdminFile', nullable: true),
    new OA\Property(property: 'duration_seconds', type: 'integer'),
])]
#[OA\Schema(schema: 'AppAdminLesson', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'program_id', type: 'integer'),
    new OA\Property(property: 'type', type: 'string', enum: ['level', 'special', 'signature']),
    new OA\Property(property: 'level', type: 'string', enum: ['beginner', 'intermediate', 'advanced'], nullable: true),
    new OA\Property(property: 'day', type: 'integer'),
    new OA\Property(property: 'favorites_count', type: 'integer'),
    new OA\Property(property: 'translations', type: 'object', properties: [new OA\Property(property: 'vi', ref: '#/components/schemas/AppAdminLessonTranslation'), new OA\Property(property: 'en', ref: '#/components/schemas/AppAdminLessonTranslation')]),
    new OA\Property(property: 'video', ref: '#/components/schemas/AppAdminVideo', nullable: true),
])]
#[OA\Schema(schema: 'AppAdminProgram', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'rating', type: 'string', nullable: true),
    new OA\Property(property: 'translations', type: 'object', properties: [new OA\Property(property: 'vi', ref: '#/components/schemas/AppAdminProgramTranslation'), new OA\Property(property: 'en', ref: '#/components/schemas/AppAdminProgramTranslation')]),
    new OA\Property(property: 'favorites_count', type: 'integer'),
    new OA\Property(property: 'lessons_count', type: 'integer'),
    new OA\Property(property: 'duration_seconds', type: 'integer'),
])]
#[OA\Schema(schema: 'AppAdminProgramDetail', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'rating', type: 'string', nullable: true),
    new OA\Property(property: 'translations', type: 'object', properties: [new OA\Property(property: 'vi', ref: '#/components/schemas/AppAdminProgramTranslation'), new OA\Property(property: 'en', ref: '#/components/schemas/AppAdminProgramTranslation')]),
    new OA\Property(property: 'favorites_count', type: 'integer'),
    new OA\Property(property: 'lessons_count', type: 'integer'),
    new OA\Property(property: 'duration_seconds', type: 'integer'),
    new OA\Property(property: 'lessons', type: 'array', items: new OA\Items(ref: '#/components/schemas/AppAdminLesson')),
])]
#[OA\Schema(schema: 'AppAdminBanner', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'translations', type: 'object', properties: [new OA\Property(property: 'vi', ref: '#/components/schemas/AppAdminBannerTranslation'), new OA\Property(property: 'en', ref: '#/components/schemas/AppAdminBannerTranslation')]),
])]
#[OA\Schema(schema: 'AppAdminInfo', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'icon', ref: '#/components/schemas/AppAdminFile', nullable: true),
    new OA\Property(property: 'translations', type: 'object', properties: [new OA\Property(property: 'vi', ref: '#/components/schemas/AppAdminInfoTranslation'), new OA\Property(property: 'en', ref: '#/components/schemas/AppAdminInfoTranslation')]),
])]
#[OA\Schema(schema: 'AppAdminCombo', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'translations', type: 'object', properties: [new OA\Property(property: 'vi', ref: '#/components/schemas/AppAdminComboTranslation'), new OA\Property(property: 'en', ref: '#/components/schemas/AppAdminComboTranslation')]),
    new OA\Property(property: 'program_ids', type: 'array', items: new OA\Items(type: 'integer')),
    new OA\Property(property: 'infos', type: 'array', items: new OA\Items(ref: '#/components/schemas/AppAdminInfo')),
])]
#[OA\Schema(schema: 'AppAdminUser', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'email', type: 'string'),
    new OA\Property(property: 'first_name', type: 'string'),
    new OA\Property(property: 'last_name', type: 'string'),
    new OA\Property(property: 'full_name', type: 'string'),
    new OA\Property(property: 'phone', type: 'string', nullable: true),
    new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true),
    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'receptionist', 'trainer', 'member']),
    new OA\Property(property: 'status', type: 'string', enum: ['active', 'locked']),
    new OA\Property(property: 'plan', type: 'string', enum: ['basic', 'plus', 'all'], nullable: true),
    new OA\Property(property: 'subscription_status', type: 'string', enum: ['trial', 'active', 'grace_period', 'expired', 'cancelled', 'refunded'], nullable: true),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'registration_source', type: 'string', enum: ['app', 'web', 'admin', 'receptionist', 'trainer'], nullable: true),
    new OA\Property(property: 'created_by', type: 'object', properties: [new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'full_name', type: 'string'), new OA\Property(property: 'role', type: 'string', enum: ['admin', 'receptionist', 'trainer'])], nullable: true),
])]
#[OA\Schema(schema: 'AppAdminSubscription', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'plan', type: 'string', enum: ['basic', 'plus', 'all'], nullable: true),
    new OA\Property(property: 'status', type: 'string', enum: ['trial', 'active', 'grace_period', 'expired', 'cancelled', 'refunded']),
    new OA\Property(property: 'provider', type: 'string', enum: ['admin', 'apple_iap', 'google_iap']),
    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'auto_renew', type: 'boolean'),
    new OA\Property(property: 'program_ids', type: 'array', items: new OA\Items(type: 'integer')),
    new OA\Property(property: 'amount', type: 'string'),
    new OA\Property(property: 'currency', type: 'string'),
    new OA\Property(property: 'billing_cycle', type: 'string'),
    new OA\Property(property: 'trial_ends_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'cancelled_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'grace_period_ends_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
])]
#[OA\Schema(schema: 'AppAdminTransaction', type: 'object', properties: [
    new OA\Property(property: 'provider', type: 'string'),
    new OA\Property(property: 'transaction_id', type: 'string'),
    new OA\Property(property: 'product_id', type: 'string'),
    new OA\Property(property: 'purchase_date', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'expires_date', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'status', type: 'string', nullable: true),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
])]
#[OA\Schema(schema: 'AppAdminProgramInput', type: 'object', properties: [
    new OA\Property(property: 'rating', type: 'number', nullable: true),
    new OA\Property(property: 'translations', type: 'object', properties: [new OA\Property(property: 'vi', ref: '#/components/schemas/AppAdminProgramTranslation'), new OA\Property(property: 'en', ref: '#/components/schemas/AppAdminProgramTranslation')]),
])]
#[OA\Schema(schema: 'AppAdminLessonInput', type: 'object', properties: [
    new OA\Property(property: 'type', type: 'string', enum: ['level', 'special', 'signature']),
    new OA\Property(property: 'level', type: 'string', enum: ['beginner', 'intermediate', 'advanced'], nullable: true),
    new OA\Property(property: 'day', type: 'integer'),
    new OA\Property(property: 'translations', type: 'object', properties: [new OA\Property(property: 'vi', ref: '#/components/schemas/AppAdminLessonTranslation'), new OA\Property(property: 'en', ref: '#/components/schemas/AppAdminLessonTranslation')]),
    new OA\Property(property: 'video', type: 'object', properties: [new OA\Property(property: 'file', ref: '#/components/schemas/AppAdminFile', nullable: true), new OA\Property(property: 'duration_seconds', type: 'integer')]),
])]
#[OA\Schema(schema: 'AppAdminBannerInput', type: 'object', properties: [
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'translations', type: 'object', properties: [new OA\Property(property: 'vi', ref: '#/components/schemas/AppAdminBannerTranslation'), new OA\Property(property: 'en', ref: '#/components/schemas/AppAdminBannerTranslation')]),
])]
#[OA\Schema(schema: 'AppAdminComboInput', type: 'object', properties: [
    new OA\Property(property: 'translations', type: 'object', properties: [new OA\Property(property: 'vi', ref: '#/components/schemas/AppAdminComboTranslation'), new OA\Property(property: 'en', ref: '#/components/schemas/AppAdminComboTranslation')]),
    new OA\Property(property: 'program_ids', type: 'array', items: new OA\Items(type: 'integer')),
    new OA\Property(property: 'infos', type: 'array', items: new OA\Items(ref: '#/components/schemas/AppAdminInfo')),
])]
#[OA\Schema(schema: 'AppAdminSubscriptionInput', type: 'object', properties: [
    new OA\Property(property: 'plan', type: 'string', enum: ['basic', 'plus', 'all'], nullable: true),
    new OA\Property(property: 'status', type: 'string', enum: ['trial', 'active', 'grace_period', 'expired', 'cancelled', 'refunded']),
    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'auto_renew', type: 'boolean'),
    new OA\Property(property: 'program_ids', type: 'array', items: new OA\Items(type: 'integer')),
])]
#[OA\Schema(schema: 'AppAdminUploadInput', type: 'object', properties: [
    new OA\Property(property: 'type', type: 'string', enum: ['banner_image', 'program_cover', 'lesson_video', 'lesson_thumbnail', 'combo_cover', 'combo_info_icon']),
    new OA\Property(property: 'filename', type: 'string'),
    new OA\Property(property: 'mimetype', type: 'string'),
    new OA\Property(property: 'size', type: 'integer'),
])]
#[OA\Schema(schema: 'AppAdminUpload', type: 'object', properties: [
    new OA\Property(property: 'upload_url', type: 'string'),
    new OA\Property(property: 'method', type: 'string'),
    new OA\Property(property: 'headers', type: 'object', properties: [new OA\Property(property: 'Content-Type', type: 'string')]),
    new OA\Property(property: 'expires_in', type: 'integer'),
    new OA\Property(property: 'file', ref: '#/components/schemas/AppAdminFile', nullable: true),
])]
#[OA\Schema(schema: 'AppAdminUserDetail', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'email', type: 'string'),
    new OA\Property(property: 'first_name', type: 'string'),
    new OA\Property(property: 'last_name', type: 'string'),
    new OA\Property(property: 'full_name', type: 'string'),
    new OA\Property(property: 'phone', type: 'string', nullable: true),
    new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true),
    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'receptionist', 'trainer', 'member']),
    new OA\Property(property: 'status', type: 'string', enum: ['active', 'locked']),
    new OA\Property(property: 'plan', type: 'string', enum: ['basic', 'plus', 'all'], nullable: true),
    new OA\Property(property: 'subscription_status', type: 'string', enum: ['trial', 'active', 'grace_period', 'expired', 'cancelled', 'refunded'], nullable: true),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'registration_source', type: 'string', enum: ['app', 'web', 'admin', 'receptionist', 'trainer'], nullable: true),
    new OA\Property(property: 'created_by', type: 'object', properties: [new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'full_name', type: 'string'), new OA\Property(property: 'role', type: 'string', enum: ['admin', 'receptionist', 'trainer'])], nullable: true),
    new OA\Property(property: 'subscription', ref: '#/components/schemas/AppAdminSubscription', nullable: true),
    new OA\Property(property: 'transactions', type: 'array', items: new OA\Items(ref: '#/components/schemas/AppAdminTransaction')),
])]
#[OA\Schema(schema: 'AppAdminBannerPage', type: 'object', properties: [
    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/AppAdminBanner')),
    new OA\Property(property: 'current_page', type: 'integer'),
    new OA\Property(property: 'last_page', type: 'integer'),
    new OA\Property(property: 'total', type: 'integer'),
    new OA\Property(property: 'per_page', type: 'integer'),
])]
#[OA\Schema(schema: 'AppAdminComboPage', type: 'object', properties: [
    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/AppAdminCombo')),
    new OA\Property(property: 'current_page', type: 'integer'),
    new OA\Property(property: 'last_page', type: 'integer'),
    new OA\Property(property: 'total', type: 'integer'),
    new OA\Property(property: 'per_page', type: 'integer'),
])]
#[OA\Schema(schema: 'AppAdminUserPage', type: 'object', properties: [
    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/AppAdminUser')),
    new OA\Property(property: 'current_page', type: 'integer'),
    new OA\Property(property: 'last_page', type: 'integer'),
    new OA\Property(property: 'total', type: 'integer'),
    new OA\Property(property: 'per_page', type: 'integer'),
])]
class AppAdminSchemas {}
