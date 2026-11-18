<?php

namespace App\Support\PaymentProcessors;

use Exception;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\Subscription;
use Stripe\Stripe as StripeLib;
use Stripe\Event as StripeEvent;
use App\Models\SubscriptionPlan;
use App\Support\PaymentProcessors\Api\Stripe;
use App\Support\PaymentProcessors\Interfaces\ActivatesSubscriptionOnReturnUrl;
use App\Support\PaymentProcessors\Interfaces\CancelsSubscription;
use App\Support\PaymentProcessors\Interfaces\CanSyncSubscriptionPlans;
use App\Support\PaymentProcessors\Interfaces\ChangesSubscription;
use App\Support\PaymentProcessors\Interfaces\RegistersWebhook;
use App\Support\PaymentProcessors\Traits\HandleChangeSubscription;
use App\Support\PaymentProcessors\Traits\HandlesReturnUrl;
use App\Support\PaymentProcessors\Traits\HandlesSyncSubscriptionPlans;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Support\Facades\Log;
use Throwable;

class StripePaymentProcessor extends PaymentProcessor implements
    CanSyncSubscriptionPlans,
    ActivatesSubscriptionOnReturnUrl,
    RegistersWebhook,
    ChangesSubscription,
    CancelsSubscription
{
    use HandleChangeSubscription;
    use WriteLogs;
    use HandlesReturnUrl;
    use HandlesSyncSubscriptionPlans;

    private Stripe $api;

    private ?StripeEvent $webhookEvent = null;

    public function __construct()
    {
        parent::__construct();

        $this->api = new Stripe($this->config('secret_key'), $this->automaticTaxEnabled());
    }

    public function slug()
    {
        return 'stripe';
    }

    protected function fetchRemoteSubscriptionFromReturnUrl($queryParams)
    {
        return $this->api->getCheckoutSession($queryParams['s_id']);
    }

    protected function makePayLink(Subscription $subscription)
    {
        try {
            $price_id = $this->getPriceId($subscription->subscription_plan);

            return $this->api->generateSubscribePayLink(
                $this->successUrl(),
                $this->canceledUrl(),
                $subscription->id,
                $price_id,
                $subscription->user->email
            );
        } catch (Throwable $th) {
            $this->logWarning($th->getMessage());
        }

        return null;
    }

    protected function changeSubscription($remoteId, SubscriptionPlan $toPlan)
    {
        $priceId = $this->getPriceId($toPlan);

        $updated = $this->api->changeSubscription($remoteId, $priceId);

        return $updated;
    }

    /**
     * @param array $remote_subscription is stripe checkout object
     */
    protected function isRemoteSubscriptionActive($remote_subscription): bool
    {
        return $remote_subscription['payment_status'] == 'paid';
    }

    protected function doTestCredentials(): bool
    {
        try {
            $product = $this->api->createProduct('test', 'test product');

            return is_string($product->id) && !empty($product->id);
        } catch (Exception $ex) {
            return false;
        }
    }

    protected function syncSubscriptionPlan(SubscriptionPlan $plan): string
    {
        $product = $this->api->createProduct($plan->name, $plan->description);

        $price = $this->api->createRecurringPrice(
            $this->resolveInterval($plan),
            $plan->price,
            $this->currencyManager->enabledCurrency()->currency_code,
            $product->id
        );

        $this->setPriceId($plan, $price->id);

        return $this->getPriceId($plan);
    }

    protected function verifyWebhook(Request $request): bool
    {
        try {
            $this->initStripeEvent($request);

            return !empty($this->webhookEvent);
        } catch (Throwable $th) {
            return false;
        }
    }

    private function initStripeEvent(Request $request)
    {
        if ($this->webhookEvent) {
            return $this->webhookEvent;
        }

        StripeLib::setApiKey(config('services.stripe.secret_key'));

        $payload = $request->getContent();

        $this->webhookEvent = null;

        try {
            $this->webhookEvent = StripeEvent::constructFrom(
                json_decode($payload, true)
            );
        } catch (Exception $e) {
        }
    }

    protected function handleVerifiedWebhook(Request $request)
    {
        $this->initStripeEvent($request);

        $paymentIntent = $this->webhookEvent->data->object;

        $checkoutSession = $this->api->getCheckoutSessionByPaymentIntent(
            $paymentIntent->id
        );

        if (!$checkoutSession) {

            $this->logDebugf(
                'Cannot get checkout session from stripe webhook, we got the following payment intent: %s',
                json_encode($paymentIntent, JSON_PRETTY_PRINT)
            );

            return;
        }

        /**
         * @var Subscription
         */
        $subscription = Subscription::find(
            $checkoutSession->metadata->subscription_id
        );

        switch ($this->webhookEvent->type) {
            case 'payment_intent.succeeded':
                $this->createTransaction(
                    $paymentIntent->id,
                    $subscription->id,
                    $paymentIntent->amount / 100,
                    $paymentIntent->currency,
                    Transaction::STATUS_SUCCESS
                );

                $this->setRemoteSubscriptionId(
                    $subscription->user,
                    $checkoutSession->subscription->id
                );

                break;
            case 'payment_intent.payment_failed':
                $this->createTransaction(
                    $paymentIntent->id,
                    $subscription->id,
                    $paymentIntent->amount / 100,
                    $paymentIntent->currency,
                    Transaction::STATUS_FAILED
                );

                break;
            default:
                Log::error(static::class . ' Received unknown event type');
        }
    }



    protected function remoteTransactionIdMetaKey()
    {
        return 'payment_intent';
    }

    /**
     * @param array $remote_subscription is stripe checkout object
     */
    protected function resolveLocalSubscription($remote_subscription): ?Subscription
    {
        return Subscription::find($remote_subscription['client_reference_id']);
    }

    public function registerWebhook(): bool
    {
        try {
            $webhooks = $this->api->listWebhooks();

            $webhooks = collect($webhooks);

            $webhook = $webhooks->first(fn ($w) => $w['url'] == $this->webhookUrl());

            if ($webhook) {
                return true;
            }

            $webhook = $this->api->registerWebhook($this->webhookUrl());

            if (!@$webhook['id']) {
                throw new Exception('Expected webhook response with valid id. Got ' . json_encode($webhook));
            }

            return true;
        } catch (Exception $ex) {
            Log::error('Cannot register webhook. ' . $ex->getMessage());
            return false;
        }
    }

    public function cancelRemoteSubscription(Subscription $subscription)
    {
        $remoteId = $this->getRemoteSubscriptionId($subscription->user);

        $response = $this->api->cancelSubscription($remoteId);

        return $response;
    }

    private function getPriceId(SubscriptionPlan $plan)
    {
        return $plan->getMeta($this->slug() . '_price_id');
    }

    private function setPriceId(SubscriptionPlan $plan, $price_id)
    {
        return $plan->setMeta($this->slug() . '_price_id', $price_id);
    }

    public function yearlyInterval(): string
    {
        return 'year';
    }

    public function monthlyInterval(): string
    {
        return 'month';
    }

    public function automaticTaxEnabled(): bool
    {
        return $this->config('automatic_tax') === 'enabled';
    }
}
