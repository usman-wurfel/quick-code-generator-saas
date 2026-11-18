@extends('index')

@section('head')

<meta name="description" content="{{ $page->meta_description }}" />

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

   .page-title {
       color: var(--primary-0);
       font-size: 1.8rem;
       text-align: center;
       
   }
</style>

@endsection

@section('body')

<qrcg-header></qrcg-header>

<h1 class="page-title">{{ $page->title }}</h1>

{!! $page->html_content !!}

@include('partials.footer')

@endsection
