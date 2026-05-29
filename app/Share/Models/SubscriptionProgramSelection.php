<?php

namespace App\Share\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $user_id
 * @property int $program_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Subscription $subscription
 * @property-read User $user
 * @property-read Program $program
 */
class SubscriptionProgramSelection extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'subscription_id',
        'user_id',
        'program_id',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
