@extends('inner-page.layout')

@section('page-title', PageTitle::makeTitle('About us'))

@section('title', t('About us'))

@section('breadcrumbs')
<a href="{{ url('/') }}">{{t('Home')}}</a>
<a href="{{ url('/about') }}">{{t('About')}}</a>
@endsection

@section('content')

{{ ContentManager::contentBlocks('About page') }}

@endsection

