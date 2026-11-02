<?php

namespace App\Share\Models;

use App\Share\Enums\FitnessClassCategory;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Lớp trong catalog phòng tập. Tên hiển thị tiếng Anh ở mọi locale, mô tả theo locale.
 *
 * @property int $id
 * @property string $slug
 * @property FitnessClassCategory $category
 * @property string $name
 * @property string|null $description
 */
class FitnessClass extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'slug',
        'category',
        'name',
    ];

    protected $translatedAttributes = [
        'description',
    ];

    protected function casts(): array
    {
        return [
            'category' => FitnessClassCategory::class,
        ];
    }
}
