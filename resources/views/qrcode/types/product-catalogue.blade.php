@extends('qrcode.types.layout')

@section('qrcode-layout-head')

@endsection

@section('page')

<div class="layout-generated-webpage">
    @foreach ($composer->categories() as $category)
    <div class="category-page" slug="{{ $category['id'] }}">
        <div class="close-button">
            @include('blue.components.icons.close-circle')
        </div>
        <div class="category-page-title">
            {{ $category['name'] }}
        </div>

        @isset($category['servingHours'])
        <div class="serving-hours">
            {{ $category['servingHours'] }}
        </div>
        @endisset

        @if (!empty($subs = $composer->subCategories($category)))
        <div class="sub-categories">
            <div class="sub-category show-all">
                {{ t('All') }}
            </div>
            @foreach ($subs as $subCategory)
            <div class="sub-category" slug="{{ $subCategory['id'] }}">
                {{ $subCategory['name'] }}
            </div>
            @endforeach
        </div>
        @endif

        <div class="menu-items">

            @foreach ($composer->items($category) as $item)
            @include('qrcode.types.product-catalogue.product-item')
            @endforeach
        </div>
    </div>
    @endforeach

    <div class="main-page">
        <img src="{{ $composer->logo() }}" class="logo" />

        <h1 class="main-line">
            {{ $composer->menuName() }}
        </h1>

        <div class="restaurant-details">
            <div class="restaurant-name">
                {{ $composer->qrcodeData('restaurant_name')}}
            </div>

            @if ($composer->qrcodeData('address'))
            <div class="restaurant-address">
                {{ $composer->qrcodeData('address')}}
            </div>
            @endif

            @if ($composer->qrcodeData('phone'))
            <div class="restaurant-phone">
                {{ $composer->qrcodeData('phone')}}
            </div>
            @endif

            <div class="opening-hours">
                @include('qrcode.components.opening-hours', ['hours' => $composer->openingHours()])
            </div>

            <div class="social-icons">
                @if (!empty($composer->qrcodeData('phone')))
                <a href="tel:{{ $composer->qrcodeData('phone') }}">
                    @include('blue.components.icons.phone')
                </a>
                @endif

                @if (!empty($composer->qrcodeData('email')))
                <a href="mailto:{{ $composer->qrcodeData('email', '#') }}">
                    @include('blue.components.icons.email')
                </a>
                @endif

                @if (!empty($composer->qrcodeData('website')))
                <a href="{{ $composer->qrcodeData('website') }}" target="_blank">
                    @include('blue.components.icons.web')
                </a>
                @endif

                @if (!empty($composer->qrcodeData('maps_url')))
                <a href="{{ $composer->qrcodeData('maps_url', '#') }}" target="_blank">
                    @include('blue.components.icons.map-marker')
                </a>
                @endif

                @include('blue.components.social-links', ['urls' => $composer->qrcodeData('socialProfiles')])
            </div>
        </div>

        <div class="categories-container">
            <div class="categories">
                @foreach ($composer->topLevelCategories() as $category)
                <div class="menu-category" slug="{{ $category['id'] }}">
                    {{ $category['name'] }}
                </div>
                @endforeach
            </div>
        </div>

        @if ($composer->designValue('custom_code_enabled') === 'enabled' &&
        !empty($composer->designValue('custom_code')))
        {!! $composer->designValue('custom_code') !!}
        @endif

    </div>
</div>

@endsection