@extends('blue.layouts.page')

@section('page-content')
<section class="payment-result">
    <div class="layout-box">
        <div class="text">
            @if ($composer->thankYouViewPath())
            @include($composer->thankYouViewPath())
            @else
            <h1>{{ t('Thank you') }}</h1>

            <p>{{ t('We have received your payment successfully.') }}</p>

            <a href="{{ url('/account/login') }}" class="button primary">{{ t('Login') }}</a>
            @endif
        </div>
    </div>
</section>
@endsection