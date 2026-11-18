@php
$variant = $variant ?? 'dark';
$src = $variant === 'light'
    ? asset('/assets/images/forge-logo-light.svg')
    : asset('/assets/images/forge-logo.svg');
@endphp

<a href="/" class="logo" title="{{ t('Logo') }}">
    <img src="{{ $src }}" alt="{{ t('Logo') }}" />
</a>
