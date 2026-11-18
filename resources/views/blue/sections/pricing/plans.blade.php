@if (ContentManager::hasAnyBlocks('Pricing: title'))
<section class="pricing" id="pricing">
    <div class="text-wrapper">
        <p class="tag-line">
            {!! ContentManager::contentBlocks('Pricing: tagline') !!}
        </p>
        <h2 class="section-title">
            {!! ContentManager::contentBlocks('Pricing: title') !!}
        </h2>
    </div>

    @php

    $billingCycle = config('pricing.default_billing_cycle');

    $billingCycle = json_decode($billingCycle) ?? $billingCycle;

    $yearlyActive = $billingCycle != 'monthly';

    @endphp

    <div class="pricing-switch">
        <div class="pricing-switch-box">
            <div class="switch-item {{ !$yearlyActive ? 'active' : '' }}" tab-id="monthly">
                {{ t('Monthly Billing') }}
            </div>
            <div class="switch-item {{ $yearlyActive ? 'active' : '' }}" tab-id="yearly">
                {{ t('Yearly Billing') }}
            </div>
        </div>
    </div>

    <div class="layout-box">
        <div class="blocks">
            <div class="items-wrapper"
                style="background-image: url({{ override_asset('/assets/images/qrcode-showcase-bg-1.png') }})">

                <div class="tag" tab-id="yearly">
                    {{ t('Paid yearly')}}
                </div>

                <div class="tag" tab-id="monthly">
                    {{ t('Paid monthly')}}
                </div>

                <div class="items">
                    @foreach ($plans as $plan)
                    <div class="item" tab-id="{{ $plan->frequency }}">
                        <h3 class="heading">
                            {{ $composer->name($plan) }} {{ t('Plan') }}
                        </h3>
                        <div class="price">
                            @if ($composer->currencyBefore())
                            <span class="currency">{{ $currency }}</span>
                            <span class="number">
                                {{ $composer->price($plan) }}
                            </span>
                            @else
                            <span class="number">
                                {{ $composer->price($plan) }}
                            </span>
                            <span class="currency">{{ $currency }}</span>
                            @endif
                            <span class="sep">/</span>

                            <span class="term">
                                @if ($plan->isYearly())
                                {{ t('Year') }}
                                @else
                                {{ t('Month') }}
                                @endif
                            </span>
                        </div>
                        <p class="below-price">
                            @if ($composer->shouldShowNumberOfUsers($plan))
                            {{ $composer->numberOfUsers($plan) }}
                            @else
                            {!! ContentManager::contentBlocks('Pricing: below price') !!}
                            @endif
                        </p>
                        <ul class="text-list">
                            <li>{{ $composer->formatTotalValue($plan->number_of_scans) }} {{ t('scans') }}</li>
                            <li>{{ $composer->formatTotalValue($plan->number_of_dynamic_qrcodes) }} {{ t('dynamic QR
                                codes') }}</li>
                            <li>{{ $composer->outlinedShapes($plan)->count() }} {{ t('shapes') }}</li>
                            <li>{{ $composer->stickers($plan)->count() }} {{ t('stickers') }}</li>
                            <li>1400+ {{ t('fonts') }}</li>
                            <li>{{ count($plan->qr_types) }} {{ t('QR code types') }}</li>
                            <li>
                                @php
                                $d = $plan->number_of_custom_domains;
                                @endphp
                                {{ $composer->formatTotalValue($d) }}
                                {{ $d > 1 || $d == -1 ? t('domains can be connected') : t('domain can be connected') }}
                            </li>

                            @if ($composer->isLarge())
                            <li>
                                {{ $plan->number_of_restaurant_menu_items }}

                                {{ t('Items can be added in Restaurant Menu.')}}
                            </li>
                            @endif
                        </ul>
                        <ul class="icons-list">

                            @if ($composer->hasCopyFeature($plan))
                            <li>{{ t('Copy QR code feature') }}</li>
                            @endif

                            @if ($composer->hasLogoFeature($plan))
                            <li>{{ t('Add logo to QR code') }}</li>
                            @endif

                            <li>{{ t('Priority support') }}</li>
                            <li>{{ t('Advanced gradient') }}</li>
                        </ul>
                        <a href="{{ $composer->checkoutUrl($plan) }}" class="button accent buy-button">
                            @if($composer->price($plan) == '0')
                            {{ t('Sign up') }}
                            @else
                            {{ $composer->checkoutText() }}
                            @endif
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif