<?php

namespace App\Support\PaymentProcessors;

use App\Interfaces\CurrencyManager;
use App\Interfaces\FileManager;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionStatus;
use App\Models\User;
use App\Interfaces\SubscriptionManager;
use App\Interfaces\TransactionManager;
use App\Interfaces\UserManager;
use App\Models\Config;
use App\Models\File;
use App\Models\Transaction;
use App\Support\Billing\AccountCreditBillingManager;
use App\Support\Billing\BillingManager;
use App\Support\PaymentProcessors\Interfaces\CancelsSubscription;
use App\Support\PaymentProcessors\Interfaces\CanChangeSubscription;
use App\Support\PaymentProcessors\Interfaces\ChangesSubscription;
use App\Support\PaymentProcessors\Interfaces\HasCustomThankYouPage;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class PaymentProcessor
{
    use WriteLogs;

    protected UserManager $users;

    protected CurrencyManager $currencyManager;

    protected SubscriptionManager $subscriptionManager;

    protected TransactionManager $transactionManager;

    protected FileManager $fileManager;

    protected BillingManager $billing;

    protected AccountCreditBillingManager $accountCredit;

    protected $testCredentialMessages = [];

    public function __construct()
    {
        $this->currencyManager = app(CurrencyManager::class);

        $this->subscriptionManager = app(SubscriptionManager::class);

        $this->transactionManager = app(TransactionManager::class);

        $this->fileManager = app(FileManager::class);

        $this->billing = app(BillingManager::class);

        $this->accountCredit = app(AccountCreditBillingManager::class);

        $this->users = app(UserManager::class);
    }

    public final function generatePayLink(User $user, SubscriptionPlan $plan)
    {
        $subscription = $this->createPendingSubscription($user, $plan);

        if ($plan->price == 0) {

            $this->subscriptionManager->activateSubscription($subscription);

            return url('/dashboard/qrcodes');
        }

        $changePlanUrl = $this->generateChangeSubscriptionLinkIfNeeded($subscription);

        $paylink = $this->makePayLink($subscription);

        return $changePlanUrl ?? $paylink;
    }

    private function generateChangeSubscriptionLinkIfNeeded(Subscription $subscription)
    {
        if (request()->input('action') !== 'change-plan') return null;

        if (!($this instanceof ChangesSubscription)) {
            $this->logDebugf('%s does not implement ChangeSubscription interface', $this->slug());
            return null;
        }

        if (!$this->canChangeSubscription($subscription->user)) {
            return null;
        }

        $link = $this->generateChangeSubscriptionLink(
            user: $subscription->user,
            toPlan: $subscription->subscription_plan,
            onSuccess: function () use ($subscription) {
                $this->subscriptionManager->activateSubscription($subscription);
            }
        );

        return $link;
    }

    abstract protected function makePayLink(Subscription $subscription);

    public function receiveWebhook(Request $request)
    {
        Log::info('Receiving webhook. ' . $this->slug());

        if (!$this->verifyWebhook($request)) {
            Log::info('Webhook is NOT verified. ' . $this->slug());
            return;
        }

        Log::debug('Webhook is verified');

        return $this->handleVerifiedWebhook($request);
    }

    /**
     * Response when HTTP GET request is made to 
     * the webhook URL
     */
    public function getWebhook(Request $request)
    {
        return '';
    }

    abstract public function slug();

    abstract protected function verifyWebhook(Request $request): bool;

    abstract protected function handleVerifiedWebhook(Request $request);

    public function displayName()
    {
        return $this->config('display_name');
    }

    public function sortOrder()
    {
        return $this->config('sort_order');
    }

    public function payButtonText()
    {
        return $this->config('pay_button_text');
    }

    protected function createPendingSubscription(User $user, SubscriptionPlan $plan): Subscription
    {
        $subscription = $this->subscriptionManager->saveSubscription([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'subscription_status' => SubscriptionStatus::STATUS_PENDING_PAYMENT
        ]);

        return $subscription;
    }

    public function successUrl()
    {
        return route('payment.success', [
            'payment_gateway' => $this->slug()
        ]);
    }

    public function canceledUrl()
    {
        return route('payment.canceled');
    }

    public function webhookUrl()
    {
        return url(sprintf('/webhooks/%s', $this->slug()));
    }

    protected function createTransaction($remote_transaction_id, $subscription_id, $amount, $currency, $status, $user_id = null, $description = null)
    {
        $transaction = $this->transactionManager->createTransaction(
            $subscription_id,
            $amount,
            $currency,
            $this->slug(),
            $status,
            $user_id,
            $description
        );

        $this->setRemoteTransactionId($transaction, $remote_transaction_id);

        return $transaction;
    }

    protected function setRemoteTransactionId(Transaction $transaction, $remote_id)
    {
        $transaction->setMeta(
            sprintf(
                '%s.%s_id',
                $this->slug(),
                $this->remoteTransactionIdMetaKey()
            ),
            $remote_id
        );
    }

    protected function remoteTransactionIdMetaKey()
    {
        return 'transaction';
    }

    public function config($key)
    {
        return Config::get($this->configKey($key));
    }

    protected function configFilePath($key)
    {
        $fileId = $this->config($key);

        $file = File::find($fileId);

        if (!$file) {
            return null;
        }

        return $this->fileManager->path($file);
    }

    protected function configKey($key)
    {
        return sprintf(
            '%s.%s.%s',
            'payment_processors',
            $this->slug(),
            $key
        );
    }

    public function setConfig($key, $value)
    {
        return Config::set($this->configKey($key), $value);
    }

    protected abstract function doTestCredentials(): bool;

    protected function getTestCredentialsMessages()
    {
        return $this->testCredentialMessages;
    }

    public function testCredentials()
    {
        return [
            'success' => $this->doTestCredentials(),
            'messages' => $this->getTestCredentialsMessages()
        ];
    }

    public function enabled()
    {
        return $this->config('enabled');
    }

    public function clientFields()
    {
        return [];
    }

    protected function setSubscriptionId(Subscription $subscription, $id)
    {
        $subscription->setMeta($this->slug() . '_subscription_id', $id);
    }

    protected function getSubscriptionId(Subscription $subscription)
    {
        $subscription->getMeta($this->slug() . '_subscription_id');
    }

    public function toArray()
    {
        return [
            'slug' => $this->slug(),
            'display_name' => $this->displayName(),
            'pay_button_text' => $this->payButtonText(),
            'client_fields' => $this->clientFields()
        ];
    }

    public function thankYouViewPath(): ?string
    {
        if ($this instanceof HasCustomThankYouPage && $this->shouldRenderCustomThankYouPage()) {
            return sprintf('payment.thankyou.%s', $this->slug());
        }

        return null;
    }

    protected function enabledCurrencyCode()
    {
        return $this->currencyManager->enabledCurrency()->currency_code;
    }
}
