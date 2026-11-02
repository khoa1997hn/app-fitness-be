<?php

namespace App\Share\Models;

use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Enums\PaymentMethod;
use App\Share\Enums\TherapyVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $code
 * @property int $user_id
 * @property int|null $created_by Nhân viên tạo hộ, null nếu hội viên tự đặt
 * @property OrderType $type
 * @property int|null $package_id
 * @property int|null $therapy_service_id
 * @property TherapyVariant|null $therapy_variant
 * @property string $name
 * @property int $amount Giá chốt lúc tạo đơn
 * @property OrderStatus $status
 * @property string|null $buyer_name
 * @property string|null $buyer_phone
 * @property string|null $buyer_email
 * @property \Carbon\Carbon|null $terms_accepted_at
 * @property string|null $terms_signature Ảnh chữ ký dạng data URL
 * @property string|null $note
 * @property PaymentMethod|null $payment_method
 * @property string|null $transaction_code
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon $created_at
 * @property-read User $user
 * @property-read User|null $creator
 * @property-read Package|null $package
 * @property-read TherapyService|null $therapyService
 */
class Order extends Model
{
    protected $fillable = [
        'code',
        'user_id',
        'created_by',
        'type',
        'package_id',
        'therapy_service_id',
        'therapy_variant',
        'name',
        'amount',
        'status',
        'buyer_name',
        'buyer_phone',
        'buyer_email',
        'terms_accepted_at',
        'terms_signature',
        'note',
        'payment_method',
        'transaction_code',
        'paid_at',
    ];

    protected $hidden = [
        'terms_signature',
    ];

    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'therapy_variant' => TherapyVariant::class,
            'amount' => 'integer',
            'status' => OrderStatus::class,
            'terms_accepted_at' => 'datetime',
            'payment_method' => PaymentMethod::class,
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function therapyService(): BelongsTo
    {
        return $this->belongsTo(TherapyService::class);
    }
}
