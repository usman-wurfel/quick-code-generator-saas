<?php

namespace App\Support\PaymentProcessors\Api;

use App\Support\System\Traits\WriteLogs;
use Stripe\StripeClient;

use Exception;

class Stripe
{
    use WriteLogs;

    private StripeClient $stripe;

    private bool $taxEnabled;

    public function __construct($secret_key, $taxEnabled)
    {
        $this->taxEnabled = $taxEnabled;

        try {
            $this->stripe = new StripeClient($secret_key);
        } catch (Exception $ex) {
        }
    }

    public function createProduct($name, $description)
    {
        $data = compact('name', 'description');

        if ($this->taxEnabled) {
            $data['tax_code'] = $this->taxCode();
        }

        return $this->stripe->products->create(compact('name', 'description'));
    }

    private function taxCode()
    {
        // SaaS category
        return 'txcd_10103000';
    }

    public function createRecurringPrice($interval, $amount, $currency, $product_id)
    {
        $data = [
            'active' => true,
            'unit_amount' => $amount * 100, // cents
            'recurring' => [
                'interval' => $interval,
                'interval_count' => 1,
            ],
            'currency' => $currency,
            'product' => $product_id
        ];

        if ($this->taxEnabled) {
            $data['tax_behavior'] = $this->taxBehavior();
        }

        return $this->stripe->prices->create($data);
    }

    private function taxBehavior()
    {
        // available options are inclusive | exclusive
        return 'exclusive';
    }

    public function getSubscription($subscription_id)
    {
        return $this->stripe->subscriptions->retrieve($subscription_id);
    }

    public function changeSubscription($subscription_id, $price_id)
    {
        $subscription = $this->getSubscription($subscription_id);

        $this->logDebugf('Updating subscription %s', json_encode($subscription, JSON_PRETTY_PRINT));

        $this->stripe->subscriptions->update(
            $subscription->id,
            [
                'cancel_at_period_end' => false,
                'proration_behavior' => 'create_prorations',
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'price' => $price_id,
                    ],
                ],
            ]
        );

        $subscription = $this->getSubscription($subscription_id);

        $this->logDebugf('Update completed %s', json_encode($subscription, JSON_PRETTY_PRINT));

        return $subscription;
    }

    public function registerWebhook($url)
    {
        return $this->stripe->webhookEndpoints->create([
            'url' => $url,
            'enabled_events' => [
                'payment_intent.succeeded',
                'payment_intent.payment_failed'
            ],
        ]);
    }

    public function listWebhooks()
    {
        return $this->stripe->webhookEndpoints->all()->data;
    }

    public function clearWebhooks()
    {
        $webhooks = $this->listWebhooks();

        foreach ($webhooks as $webhook) {
            $this->stripe->webhookEndpoints->delete($webhook->id);
        }
    }

    public function generateSubscribePayLink($success_url, $cancel_url, $local_subscription_id, $stripe_price_id, $email)
    {
        $return_url = $success_url . (strpos($success_url, '?') ? '&' : '?') . 's_id={CHECKOUT_SESSION_ID}';

        $data = [
            'success_url' => $return_url,
            'cancel_url' => $cancel_url,
            'mode' => 'subscription',
            'client_reference_id' => $local_subscription_id,
            'line_items' => [
                [
                    'price' => $stripe_price_id,
                    'quantity' => 1,
                ]
            ],
            'metadata' => [
                'subscription_id' => $local_subscription_id
            ],
            'customer_email' => $email
        ];

        if ($this->taxEnabled) {
            $data['automatic_tax'] = [
                'enabled' => true
            ];
        }

        $session = $this->stripe->checkout->sessions->create($data);

        return $session->url;
    }

    public function getCheckoutSessionByPaymentIntent($id)
    {
        $data = $this->stripe->checkout->sessions->all([
            'limit' => 1,
            'payment_intent' => $id
        ])->data;

        $session = @$data[0];

        return $this->getCheckoutSession($session?->id);
    }

    public function getCheckoutSession($id)
    {
        if (!$id) return null;

        return $this->stripe->checkout->sessions->retrieve($id, [
            'expand' => ['subscription']
        ]);
    }

    public function cancelSubscription($id)
    {
        if (!$id) return;

        return $this->stripe->subscriptions->cancel($id);
    }
}
