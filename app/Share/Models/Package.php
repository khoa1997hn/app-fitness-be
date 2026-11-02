<?php

namespace App\Share\Models;

use App\Share\Enums\PackageStatus;
use App\Share\Enums\PackageType;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gói tập tại phòng tập offline — không liên quan Plan/Subscription của app video.
 *
 * @property int $id
 * @property PackageType $package_type
 * @property int $price
 * @property int $duration_days
 * @property int|null $class_credits null = không giới hạn buổi
 * @property int $session_duration_minutes
 * @property bool $is_off_peak
 * @property int $base_people_max
 * @property int $extra_people_session_max
 * @property PackageStatus $status
 * @property string $name
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Package extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'package_type',
        'price',
        'duration_days',
        'class_credits',
        'session_duration_minutes',
        'is_off_peak',
        'base_people_max',
        'extra_people_session_max',
        'status',
    ];

    protected $translatedAttributes = [
        'name',
        'description',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    protected function casts(): array
    {
        return [
            'package_type' => PackageType::class,
            'price' => 'integer',
            'duration_days' => 'integer',
            'class_credits' => 'integer',
            'session_duration_minutes' => 'integer',
            'is_off_peak' => 'boolean',
            'base_people_max' => 'integer',
            'extra_people_session_max' => 'integer',
            'status' => PackageStatus::class,
        ];
    }
}
