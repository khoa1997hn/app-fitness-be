<?php

namespace App\Share\Models;

use App\Share\Attributes\File;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $description
 * @property File $image
 * @property string|null $link_url
 * @property int $order
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Banner extends Model implements TranslatableContract
{
    use HasFactory;
    use Translatable;

    protected static function newFactory()
    {
        return \Database\Factories\BannerFactory::new();
    }

    protected $fillable = [
        'description',
    ];

    protected $translatedAttributes = [
        'image',
        'link_url',
        'order',
        'is_active',
    ];
}
