<?php

namespace App\Share\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Share\Enums\Gender;
use App\Share\Enums\Plan;
use App\Share\Enums\RegistrationSource;
use App\Share\Enums\Role;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Enums\UserStatus;
use App\Share\Models\Traits\User\ManagesSubscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property RegistrationSource|null $registration_source
 * @property int|null $created_by
 * @property-read User|null $creator
 * @property int $id
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $first_name
 * @property string $last_name
 * @property string $password
 * @property string|null $phone
 * @property \Carbon\Carbon|null $dob
 * @property SubscriptionStatus|null $subscription_status
 * @property Plan|null $plan
 * @property Role $role
 * @property UserStatus $status
 * @property Gender|null $gender
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
        'gender',
        'status',
        'subscription_status',
        'plan',
        'role',
        'registration_source',
        'created_by',
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
            'email_verified_at' => 'datetime',
            'dob' => 'date',
            'subscription_status' => SubscriptionStatus::class,
            'plan' => Plan::class,
            'role' => Role::class,
            'registration_source' => RegistrationSource::class,
            'status' => UserStatus::class,
            'gender' => Gender::class,
        ];
    }

    /**
     * Chỉ tài khoản tự đăng ký phải xác thực email, tài khoản do nhân viên tạo thì không.
     */
    public function needsEmailVerification(): bool
    {
        return $this->email_verified_at === null
            && in_array($this->registration_source?->value, [RegistrationSource::App, RegistrationSource::Web], true);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by')->withTrashed();
    }

    /** The source is a snapshot of the actor's role when the account was created. */
    public function registrationProvenance(): array
    {
        return [
            'registration_source' => $this->registration_source,
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'full_name' => trim($this->creator->first_name.' '.$this->creator->last_name),
                'role' => $this->registration_source,
            ] : null,
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

    /**
     * Kiểm tra quyền theo `config/permissions.php`. Deny by default.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($this->role?->value, config("permissions.{$permission}", []), true);
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
