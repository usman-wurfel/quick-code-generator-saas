@extends('blue.layouts.main')

@section('head')
@parent

<style>
    .container {
        margin: 0 1rem;
    }

    @media (min-width: 90rem) {
        .container {
            max-width: 80%;
            margin: auto;
        }
    }

    .main {
        display: grid;
        margin-bottom: 1rem;
    }

    @media (min-width: 800px) {
        .main {
            /* grid-template-columns: 3fr 1fr;
           grid-gap: 1rem; */
        }
    }

    .page-header {
        margin: 2rem 0;
    }

    .page-header h1 {
        margin-bottom: 1rem;
    }

    qrcg-breadcrumbs {
        margin: 1rem 0;
    }

    .layout-title {
        color: var(--primary-0);
        font-size: 1.8rem;
    }
</style>

@endsection

@section('body')

<qrcg-header></qrcg-header>

<div class="container">

    @hasSection ('breadcrumbs')
    <qrcg-breadcrumbs>
        @yield('breadcrumbs')
    </qrcg-breadcrumbs>
    @endif

    <div class="page-header">
        <h1 class="layout-title">@yield('title')</h1>
        @yield('below-title')
    </div>

    <div class="main">
        <div class="content">
            @yield('content')
        </div>
    </div>
</div>

@include('partials.footer')

@endsection