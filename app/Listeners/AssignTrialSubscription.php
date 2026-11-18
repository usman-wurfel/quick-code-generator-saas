<?php

namespace App\Listeners;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionStatus;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AssignTrialSubscription
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserCreated  $event
     * @return void
     */
    public function handle(Registered $event)
    {
        $plan = $this->getDefaultPlan();

        $subscription = new Subscription(
            [
                'subscription_plan_id' => $plan->id,
                'user_id' => $event->user->id,
            ]
        );

        $subscription->save();

        $status = new SubscriptionStatus([
            'subscription_id' => $subscription->id,
            'status' => SubscriptionStatus::STATUS_ACTIVE
        ]);

        $status->save();
    }

    private function getDefaultPlan()
    {
        $trial = SubscriptionPlan::where('is_trial', true)->first();

        if (!$trial) {
            return $this->getFallbackPlan();
        }

        return $trial;
    }

    private function getFallbackPlan()
    {
        return SubscriptionPlan::orderBy('price', 'asc')->first();
    }
}
