<?php

namespace App\Share\Models;

use App\Share\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;

/**
 * Thông báo trong trang tài khoản của hội viên.
 *
 * @property int $id
 * @property int $user_id
 * @property NotificationType $type
 * @property string $message
 * @property string|null $dedupe_key Chống gửi lại thông báo định kỳ
 * @property \Carbon\Carbon|null $read_at
 */
class UserNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'dedupe_key',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'read_at' => 'datetime',
        ];
    }
}
