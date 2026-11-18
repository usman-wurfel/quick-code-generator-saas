<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\PaymentProcessors\PayPalPaymentProcessor;

/**
 * @group recent
 */
class PaymentProcessorTest extends TestCase
{
    public function test_paypal_generate_paylink()
    {
        $paypalProcessor = new PayPalPaymentProcessor();

        $user = User::whereEmail('admin@gmail.com')->first();

        $plan = SubscriptionPlan::whereName('PRO')->first();

        $paypalProcessor->syncPlan($plan);

        $link = $paypalProcessor->generatePayLink($user, $plan);

        $this->assertNotEmpty($link);

        dd($link);
    }
}
