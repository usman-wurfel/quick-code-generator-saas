<?php

namespace App\Models;

use App\Events\SavingSubscription;
use App\Events\SubscriptionCreated;
use App\Models\Traits\HasMetaFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property User user
 * @property SubscriptionPlan subscription_plan
 * @property Collection statuses
 */
class Subscription extends Model
{
    use HasFactory, HasMetaFields;

    protected $fillable = [
        'subscription_plan_id', 'user_id', 'paypal_id', 'paypal_order_id'
    ];

    public function statuses()
    {
        return $this->hasMany(SubscriptionStatus::class)->orderBy('id', 'desc');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription_plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
