<?php

namespace App\Share\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Models\Traits\User\ManagesSubscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property int $id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string $password
 * @property string|null $phone
 * @property \Carbon\Carbon|null $dob
 * @property SubscriptionStatus|null $subscription_status
 * @property Plan|null $plan
 * @property-read Subscription|null $subscription
 * @property-read Subscription|null $validSubscription
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Lesson> $favoriteLessons
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Program> $favoritePrograms
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, ManagesSubscription, Notifiable, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'password',
        'phone',
        'dob',
        'subscription_status',
        'plan',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'dob' => 'date',
            'subscription_status' => SubscriptionStatus::class,
            'plan' => Plan::class,
        ];
    }

    public function favoriteLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_favorites')
            ->withTimestamps();
    }

    public function favoritePrograms(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_favorites')
            ->withTimestamps();
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->first_name,
            'phone' => $this->phone,
            'dob' => $this->dob,
            'plan' => $this->plan?->value,
            'subscription_status' => $this->subscription_status?->value,
        ];
    }
}
