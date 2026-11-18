<?php

namespace App\Interfaces;

use App\Models\Subscription;
use App\Models\User;

interface SubscriptionManager
{
    public function setExpiredSubscriptions();

    public function saveSubscription($data);

    public function activateSubscription(Subscription $subscription, $forceAssignNewStatus = false);

    public function deactivateSubscription(Subscription $subscrption);

    public function userHasActiveSubscription(User $user);

    public function userDynamicQRCodesLimitReached(User $user);

    public function userScanLimitReached(User $user);

    public function userInvitedUsersLimitReached(User $user);

    public function subscriptionIsExpired(Subscription $subscription);

    public function getSubscriptionAgeInDays(Subscription $subscription);

    public function getSubscriptionRemainingDays(Subscription $subscription);

    public function userDomainsLimitReached(User $user);

    public function userAiGenerationsLimitReached(User $user);

    public function userOnTrialPlan(User $user): bool;

    public function shouldEnforceSubscriptionRules(User $user = null);

    public function userHasAccessToSubscriptionPlanFeature(User $user, string $feature);

    public function cancelSubscription(Subscription $subscription);
}
