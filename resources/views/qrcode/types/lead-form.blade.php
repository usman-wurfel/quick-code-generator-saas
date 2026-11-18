@extends('qrcode.types.layout')

@section('qrcode-layout-head')

@endsection

@section('page')

<div class="layout-generated-webpage">
    @include('qrcode.components.lead-form.lead-form', ['id' => $composer->leadFormId(), 'mode' => 'full-page' ])
</div>

@endsection