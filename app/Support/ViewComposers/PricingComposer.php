<?php

namespace App\Support\ViewComposers;

use App\Interfaces\CurrencyManager;
use App\Models\SubscriptionPlan;
use App\Support\Billing\AccountCreditBillingManager;
use App\Support\Billing\BillingManager;
use App\Support\DropletManager;
use App\Support\QRCodeTypes\BaseType;
use App\Support\QRCodeTypes\QRCodeTypeManager;
use App\Support\System\Translation\LineTranslator;
use Illuminate\View\View;


class PricingComposer extends BaseComposer
{
    private CurrencyManager $currencies;

    private DropletManager $dropletManager;

    private QRCodeTypeManager $qrcodeTypeManager;

    private BillingManager $billingManager;

    private AccountCreditBillingManager $accountCredit;

    public function __construct(CurrencyManager $currencies)
    {
        parent::__construct();

        $this->currencies = $currencies;

        $this->dropletManager = new DropletManager();

        $this->billingManager = new BillingManager();

        $this->qrcodeTypeManager = new QRCodeTypeManager();

        $this->accountCredit = new AccountCreditBillingManager;
    }

    public static function path(): string
    {
        return 'blue.sections.pricing|blue.sections.pricing.account-credit|blue.sections.pricing.plans';
    }

    private function getPlans()
    {
        return SubscriptionPlan::where('is_hidden', false)->get();
    }

    public function isAccountCreditBilling()
    {
        return $this->billingManager->isAccountCreditBilling();
    }

    public function dynamicQRCodePrice()
    {
        return $this->accountCredit->dynamicQRCodePrice();
    }

    public function staticQRCodePrice()
    {
        return $this->accountCredit->staticQRCodePrice();
    }

    public function getDynamicTypes()
    {
        return $this->qrcodeTypeManager->types()->filter(function (BaseType $type) {
            return $type->isDynamic();
        });
    }

    public function getStaticTypes()
    {
        return $this->qrcodeTypeManager->types()->filter(function (BaseType $type) {
            return !$type->isDynamic();
        });
    }

    public function price(SubscriptionPlan $plan)
    {
        return number_format($plan->price, 2, ".", "");
    }

    public function outlinedShapes(SubscriptionPlan $plan)
    {
        return collect($plan->features)->filter(
            fn ($f) => preg_match("/^shape\./", $f)
        );
    }

    public function stickers(SubscriptionPlan $plan)
    {
        return collect($plan->features)->filter(
            fn ($f) => preg_match("/^advancedShape\./", $f)
        );
    }

    public function name(SubscriptionPlan $plan)
    {
        if (!$this->translations->multilingualEnabled()) {
            return $plan->name;
        }

        $lineTranslator = new LineTranslator;

        return $lineTranslator->translateLine($plan, 'name');
    }

    public function hasCopyFeature(SubscriptionPlan $plan)
    {
        return collect($plan->features)->filter(
            fn ($f) => preg_match('/qrcode.copy/', $f)
        )->count() > 0;
    }

    public function hasLogoFeature(SubscriptionPlan $plan)
    {
        return collect($plan->features)->filter(
            fn ($f) => preg_match('/qrcode.logo/', $f)
        )->count() > 0;
    }

    public function formatTotalValue($value)
    {
        return $value == -1 ? t('Unlimited') : $value;
    }

    public function currencyBefore()
    {
        $position = $this->currencies->enabledCurrency()->symbol_position;

        return empty($position) || $position == 'before';
    }

    public function shouldShowNumberOfUsers(SubscriptionPlan $plan)
    {
        if (!$this->dropletManager->isLarge()) return false;

        if ($plan->number_of_users == -1) {
            return true;
        }

        return $plan->number_of_users > 0;
    }

    public function numberOfUsers($plan)
    {
        $num = $this->formatTotalValue($plan->number_of_users);

        if ($num == 1) {
            $text = t('one user can be invited.');
        } else {
            $text = t('users can be invited.');
        }

        return "$num $text";
    }

    public function isLarge()
    {
        return $this->dropletManager->isLarge();
    }

    private function isChangePlanRequest()
    {
        return request()->input('action') === 'change-plan';
    }

    public function checkoutUrl(SubscriptionPlan $plan)
    {
        $changePlanParam = '';

        if ($this->isChangePlanRequest()) {
            $changePlanParam = '&action=change-plan';
        }

        return sprintf(
            '%s?plan-id=%s%s',
            url('/checkout'),
            $plan->id,
            $changePlanParam
        );
    }

    public function checkoutText()
    {
        if ($this->isChangePlanRequest())
            return t('Change Plan');

        return t('Subscribe now');
    }

    public function compose(View $view)
    {
        parent::compose($view);

        $view->with('plans', $this->getPlans());

        $view->with('currency', $this->currencies->enabledCurrency()->symbol);
    }
}
