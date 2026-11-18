@extends('qrcode.types.skeleton')

@section('favicon')

@include('qrcode.components.favicon')

@endsection

@section('head')
@parent

{!! $composer->styles() !!}

@section('qrcode-layout-head')
@show

@endsection

@section('title')
{{ $composer->getQRCode()->name }}
@endsection

@section('dashboard-assets')
@endsection

@section('body')

<div class="loader">
    <div class="lds-ring">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    </div>
</div>

@section('page')
@show

@section('powered-by')
@if ($composer->shouldShowPoweredBy())
<div class="powered-by">
    {{ t('Powered by') }}
    <a href="{{ config('app.url') }}" class="powered-by">{{ $composer->poweredByName() }}</a>
</div>
@endif
@show

@endsection