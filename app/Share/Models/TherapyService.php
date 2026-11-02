<?php

namespace App\Share\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Dịch vụ trị liệu Recovery.
 *
 * @property int $id
 * @property string $slug
 * @property int $single_price
 * @property int $single_duration_minutes
 * @property int $combo5_price
 * @property string $name
 * @property string|null $long_description
 * @property list<string>|null $benefits
 */
class TherapyService extends Model implements TranslatableContract
{
    use Translatable;

    /** Số buổi của Combo 5, cố định theo tên sản phẩm */
    public const COMBO5_SESSIONS = 5;

    protected $fillable = [
        'slug',
        'single_price',
        'single_duration_minutes',
        'combo5_price',
    ];

    protected $translatedAttributes = [
        'name',
        'long_description',
        'benefits',
    ];

    protected function casts(): array
    {
        return [
            'single_price' => 'integer',
            'single_duration_minutes' => 'integer',
            'combo5_price' => 'integer',
        ];
    }
}
