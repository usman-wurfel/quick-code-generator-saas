@extends('qrcode.types.layout')

@section('qrcode-layout-head')

{!! $composer->blockStyleTags() !!}

@endsection

@section('page')

<div class="layout-generated-webpage">
    <div class="details-container">

        {!! ContentManager::customCode('BioLinks: start') !!}

        @foreach ($composer->blocks() as $block)

        {!! ContentManager::customCode(sprintf('BioLinks %s: before', $block::slug())) !!}

        {!! $block->render() !!}

        {!! ContentManager::customCode(sprintf('BioLinks %s: after', $block::slug())) !!}

        @endforeach

        {!! ContentManager::customCode('BioLinks: end') !!}

    </div>
</div>

@php
$action = PluginManager::ACTION_BIOLINKS_AFTER_LAYOUT;

echo PluginManager::doAction($action, $composer->getQRCode())
@endphp

@endsection