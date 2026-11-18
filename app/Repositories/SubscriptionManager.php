<?php

namespace App\Repositories;

use App\Events\SubscriptionCanceled;
use App\Interfaces\SubscriptionManager as SubscriptionManagerInterface;
use App\Interfaces\UserManager;
use App\Models\Config;
use App\Models\Subscription;

use App\Models\SubscriptionStatus;
use App\Models\User;
use App\Support\AI\UsageManager;
use App\Support\Billing\BillingManager;
use App\Support\DomainManager;
use App\Support\QRCodeManager;
use App\Support\QRCodeScanManager;
use App\Support\QRCodeTypes\QRCodeTypeManager;
use App\Support\System\Traits\WriteLogs;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SubscriptionManager implements SubscriptionManagerInterface
{
    use WriteLogs;

    private QRCodeTypeManager $qrcodeTypes;
    private QRCodeManager $qrcodes;
    private UserManager $users;

    private BillingManager $billing;

    public function __construct()
    {
        $this->qrcodeTypes = new QRCodeTypeManager;
        $this->qrcodes = new QRCodeManager;
        $this->users = app(UserManager::class);

        $this->billing = new BillingManager();
    }

    public function setExpiredSubscriptions()
    {
        $subscriptions = Subscription::with('statuses', 'subscription_plan')->get();

        /** @var Subscription */
        foreach ($subscriptions as $subscription) {
            $status = $subscription->statuses->first();

            if (!$status) {
                Log::warning(
                    "Subscription without statuses, those are likely test users",
                    [
                        'id' => $subscription->id,
                        'user_id' => $subscription->user_id
                    ]
                );
                continue;
            }

            if ($status->status === SubscriptionStatus::STATUS_EXPIRED) {
                continue;
            }

            if ($status->status === SubscriptionStatus::STATUS_ACTIVE) {

                if ($this->subscriptionIsExpired($subscription)) {
                    // Add expired status to this subscription
                    $this->assignExpiredStatus($subscription);
                }
            }
        }
    }

    public function getSubscriptionRemainingDays(Subscription $subscription)
    {
        if (!$this->shouldEnforceSubscriptionRules($subscription->user)) return null;

        $ageInDays = $this->getSubscriptionAgeInDays($subscription);

        $plan = $subscription->subscription_plan;

        $planExpirationDays = $plan->exiprationDays();

        return $planExpirationDays - $ageInDays;
    }

    public function getSubscriptionAgeInDays(Subscription $subscription)
    {
        if (
            !$this->shouldEnforceSubscriptionRules($subscription->user)
        ) return null;

        if (
            $subscription->statuses[0]->status ===
            SubscriptionStatus::STATUS_EXPIRED
        ) return 0;

        $activeStatus = $subscription
            ->statuses->first(
                fn ($s) => $s->status === SubscriptionStatus::STATUS_ACTIVE
            );

        if (!$activeStatus) return 0;

        $days = Carbon::now()->diffInDays($activeStatus->created_at);

        return $days;
    }

    public function subscriptionIsExpired(Subscription $subscription)
    {
        if (!$this->shouldEnforceSubscriptionRules($subscription->user)) return false;

        /** @var \App\Models\SubscriptionPlan */
        $plan = $subscription->subscription_plan;

        $planExpirationDays = $plan->exiprationDays();

        $days = $this->getSubscriptionAgeInDays($subscription);

        if ($days === null) {
            return false;
        }

        return $days > $planExpirationDays;
    }

    public function activateSubscription(Subscription $subscription, $forceAssignNewStatus = false)
    {
        $status = SubscriptionStatus::STATUS_ACTIVE;

        if (!$forceAssignNewStatus && $subscription->statuses[0]?->status == $status) {
            return;
        }

        $this->assignStatus($subscription, $status);
    }

    public function deactivateSubscription(Subscription $subscrption)
    {
        if ($subscrption->statuses[0]->status != SubscriptionStatus::STATUS_ACTIVE) {
            return;
        }

        $subscrption->statuses[0]->delete();
    }

    private function assignExpiredStatus($subscription)
    {
        $this->assignStatus($subscription, SubscriptionStatus::STATUS_EXPIRED);
    }

    private function assignStatus($subscription, string $status)
    {
        $found = array_filter(
            SubscriptionStatus::getStatuses(),
            fn ($s) => $s === $status
        );

        if (empty($found)) {
            throw new InvalidArgumentException("Status ($status) is invalid, expected on of: " . implode(', ', SubscriptionStatus::getStatuses()));
        }

        $model = new SubscriptionStatus();

        $model->status = $status;

        $model->subscription_id = $subscription->id;

        $model->save();
    }

    public function saveSubscription($data)
    {
        $subscription = new Subscription();

        if (isset($data['id'])) {
            $subscription = Subscription::findOrFail($data['id']);
        }

        $subscription->fill($data);

        $subscription->save();

        if ($subscription->statuses->first()?->status !== $data['subscription_status']) {
            $this->assignStatus($subscription, $data['subscription_status']);
        }

        $subscription->refresh();

        return $subscription;
    }

    public function userHasActiveSubscription(User $user)
    {
        if ($user->isSuperAdmin()) return true;

        $subscription = $this->users->getCurrentSubscription($user);

        if (!$subscription) return false;

        return $subscription->statuses[0]->status === SubscriptionStatus::STATUS_ACTIVE;
    }

    public function userOnTrialPlan(User $user): bool
    {
        $subscription = $this->users->getCurrentSubscription($user);

        if (!$subscription) return false;

        if ($user->isSuperAdmin()) return false;

        return $subscription->subscription_plan->is_trial;
    }

    public function userDynamicQRCodesLimitReached(User $user)
    {
        if (!$this->shouldEnforceSubscriptionRules($user)) return false;

        $dynamicTypes = $this->qrcodeTypes->dynamicSlugs();

        $count = $this->qrcodes->getQRCodeCount($user, null, $dynamicTypes);

        $plan = $this->users->getCurrentPlan($user);

        if (!$plan) {
            return false;
        }

        if (
            $this->isTotalNumberUnlimited(
                $plan->number_of_dynamic_qrcodes
            )
        ) {
            return false;
        }

        return $count >= $plan->number_of_dynamic_qrcodes;
    }


    public function userScanLimitReached(User $user)
    {
        if (!$this->shouldEnforceSubscriptionRules($user)) return false;

        $scans = new QRCodeScanManager();

        $plan = $this->users->getCurrentPlan($user);

        if (!$plan) {
            return false;
        }

        if (
            $this->isTotalNumberUnlimited(
                $plan->number_of_scans
            )
        ) {

            return false;
        }

        return $scans->getScansByUser($user) >= $plan->number_of_scans;
    }

    public function userDomainsLimitReached(User $user)
    {
        if (!$this->shouldEnforceSubscriptionRules($user)) return false;

        $plan = $this->users->getCurrentPlan($user);

        if (!$plan) return false;

        if (
            $this->isTotalNumberUnlimited(
                $plan->number_of_custom_domains
            )
        ) {
            return false;
        }

        $domainManager = new DomainManager();

        return $domainManager->getPublishedDomainsOfUser($user)->count()
            >= $plan->number_of_custom_domains;
    }

    public function userInvitedUsersLimitReached(User $user)
    {
        if (!$this->shouldEnforceSubscriptionRules($user)) return false;

        $plan = $this->users->getCurrentPlan($user);

        if (
            $this->isTotalNumberUnlimited(
                $plan->number_of_users
            )
        ) {
            return false;
        }

        return $user->sub_users()->count() >= $plan->number_of_users;
    }

    public function userAiGenerationsLimitReached(User $user)
    {
        if (!$this->shouldEnforceSubscriptionRules($user)) return false;

        /**
         * @var \App\Models\SubscriptionPlan
         */
        $plan = $this->users->getCurrentPlan($user);

        if (
            $this->isTotalNumberUnlimited(
                $plan->number_of_ai_generations
            )
        ) {
            return false;
        }

        return UsageManager::forUser($user)->getUsage() >= $plan->number_of_ai_generations;
    }

    public function shouldEnforceSubscriptionRules(User $user = null)
    {
        if ($user && $user->isSuperAdmin()) return false;

        if (config('app.wplus_integration_enabled')) return false;

        if (
            Config::get('app.paid_subscriptions') && Config::get('app.paid_subscriptions') == 'disabled'
        ) return false;


        if ($this->billing->isAccountCreditBilling()) return false;

        return true;
    }

    public function userHasAccessToSubscriptionPlanFeature(User $user, string $feature)
    {
        if (!$this->shouldEnforceSubscriptionRules($user)) return true;

        $plan = $this->users->getCurrentPlan($user);

        return collect($plan->features)->filter(
            fn ($f) => preg_match("/$feature/", $f)
        )->count() > 0;
    }

    private function isTotalNumberUnlimited($value)
    {
        return $value == -1;
    }

    public function cancelSubscription(Subscription $subscription)
    {
        $status = SubscriptionStatus::STATUS_CANCELED;

        $this->assignStatus($subscription, $status);

        $subscription->refresh();

        $this->logDebugf('Canceling subscription %s', $subscription->id);

        event(new SubscriptionCanceled($subscription));
    }
}
