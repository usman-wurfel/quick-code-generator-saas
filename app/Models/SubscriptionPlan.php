<?php

namespace App\Models;

use App\Events\SavingSubscriptionPlan;
use App\Events\SubscriptionPlanDeleted;
use App\Events\SubscriptionPlanSaved;
use App\Models\Traits\HasMetaFields;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * @property string name
 * @property string frequency
 * @property string description
 * @property float price
 * @property float monthly_price
 * @property float yearly_price
 * @property int number_of_dynamic_qrcodes
 * @property int number_of_scans
 * @property int number_of_custom_domains
 * @property int number_of_users
 * @property int number_of_restaurant_menu_items
 * @property int number_of_product_catalogue_items
 * @property int number_of_ai_generations
 * @property bool is_popular
 * @property bool is_hidden
 * @property bool is_trial
 * @property int trial_days
 * @property array qr_types Allowed types by this plan
 * @property array features
 * @property string paypal_plan_id
 * @property string paypal_product_id
 * @property string stripe_price_id
 * @property string stripe_product_id
 */
class SubscriptionPlan extends Model
{
    use HasFactory, HasMetaFields;

    /**
     * Available values: DAY WEEK MONTH YEAR
     */
    const FREQUENCY_YEARLY = 'yearly';

    const FREQUENCY_MONTHLY = 'monthly';

    protected $fillable = [
        'name',
        'price',
        'frequency',
        'monthly_price',
        'number_of_ai_generations',
        'number_of_dynamic_qrcodes',
        'number_of_scans',
        'number_of_restaurant_menu_items',
        'number_of_product_catalogue_items',
        'is_popular',
        'is_hidden',
        'is_trial',
        'trial_days',
        'qr_types',
        'features',
        'number_of_custom_domains',
        'number_of_users',
    ];

    protected $appends = [
        'yearly_price'
    ];

    protected $casts = [
        'is_popular' => 'boolean',
        'is_hidden' => 'boolean',
        'is_trial' => 'boolean',
        'qr_types' => 'array',
        'features' => 'array'
    ];

    protected $dispatchesEvents = [
        'saving' => SavingSubscriptionPlan::class,
        'saved' => SubscriptionPlanSaved::class,
        'deleted' => SubscriptionPlanDeleted::class
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function (SubscriptionPlan $model) {
            $model->qr_types = $model->qr_types ?? [];

            $model->features = $model->features ?? [];

            $model->frequency = $model->frequency ?? $model::FREQUENCY_MONTHLY;
        });
    }

    public function yearlyPrice(): Attribute
    {
        return new Attribute(fn () => bcmul(12, $this->monthly_price, 1));
    }

    public function description(): Attribute
    {
        return new Attribute(fn () => implode(', ', [
            $this->name . ' ' . t('Plan'),
            t($this->frequency),
            $this->number_of_dynamic_qrcodes . ' ' . t('Dynamic QR Codes'),
            $this->number_of_scans . ' ' . t('Scans')
        ]));
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function exiprationDays()
    {
        if ($this->is_trial) {
            return $this->trial_days;
        }

        if ($this->frequency === $this::FREQUENCY_MONTHLY)
            return 30;

        if ($this->frequency === $this::FREQUENCY_YEARLY)
            return 365;

        throw new LogicException("Frequency must be either " . $this::FREQUENCY_MONTHLY . " or " . $this::FREQUENCY_YEARLY);
    }

    public function isMonthly()
    {
        return $this->frequency === $this::FREQUENCY_MONTHLY;
    }

    public function isYearly()
    {
        return $this->frequency === $this::FREQUENCY_YEARLY;
    }
}
