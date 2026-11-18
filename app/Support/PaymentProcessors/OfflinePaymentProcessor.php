<?php

namespace App\Support\PaymentProcessors;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Support\PaymentProcessors\Interfaces\ForwardsApiCalls;
use App\Support\PaymentProcessors\Interfaces\SelfHostedPaymentProcessor as InterfacesSelfHostedPaymentProcessor;
use App\Support\PaymentProcessors\Traits\RendersSelfHostedRoutes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OfflinePaymentProcessor extends PaymentProcessor
implements InterfacesSelfHostedPaymentProcessor, ForwardsApiCalls
{
    use RendersSelfHostedRoutes;

    public function slug()
    {
        return 'offline-payment';
    }

    protected function verifyWebhook(Request $request): bool
    {
        return false;
    }

    protected function handleVerifiedWebhook(Request $request)
    {
    }

    protected function doTestCredentials(): bool
    {
        return false;
    }

    public function registerWebhook(): bool
    {
        return false;
    }

    public function forwardedSubscribe($data)
    {
        $subscription_plan_id = @$data['subscription_plan_id'];

        return $this->createPendingSubscription(
            request()->user(),
            SubscriptionPlan::findOrFail($subscription_plan_id)
        );
    }

    public function clientFields()
    {
        return [
            'customer_instructions' => Str::markdown($this->config('customer_instructions')),
        ];
    }
}
