@extends('index')

@section('head')
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
           grid-template-columns: 3fr 1fr;
           grid-gap: 1rem;
       }
   }

   .content {
       background-color: white;
   }

   .sidebar {
       background-color: white;
       padding: 1rem;
       margin-top: 1rem;
   }

   @media (min-width: 800px) {
       .sidebar {
           margin-top: 0;
       }
   }

   .sidebar ul {
        list-style: none;
        margin: 0;
        padding: 0;
   }

   .sidebar .title {
        margin: 0;
        padding-bottom: .5rem;
        border-bottom: 2px solid var(--gray-0);
        color: var(--primary-1);
        font-size: 1.8;
   }

   .sidebar li a {
       color: var(--primary-0);
       display: inline-block;
       margin-top: 1rem;
       line-height: 1.5;
       text-decoration: none;
   }

   .sidebar li a.active {
       color: var(--dark);
   }

   .page-header {
       margin: 2rem 0;
   }

   .page-header h1 {
       margin-bottom: 1rem;
   }

   .pagination {
        display: flex;
        list-style: none;
        justify-content: center;
        margin: 0;
        font-size: 1.3rem;
        flex-wrap: wrap;
        padding: 0;
        user-select: none;
        touch-action: manipulation;
        -webkit-user-select: none;
   }

   .pagination li {

   }

   .pagination a {
       display: inline-block;
       color: var(--primary-0);
       text-decoration: none;
       padding: 1rem;
   }

   .pagination span {
       padding: 1rem;
       display: inline-block;
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
            {!! ContentManager::customCode('Blog: before content') !!}
            @yield('content')
        </div>
        <div class="sidebars">
            <div class="sidebar">
                <qrcg-h3 class="title">
                    {{ t('Recent posts') }}
                </qrcg-h3>
                <ul>
                @foreach ($recentPosts as $post)
                    <li>
                        <a href="{{ $post->url }}" @class(
                            [
                                'active' => request()->route('post')?->id === $post->id
                            ]
                        )>
                            {{ $post->title }}
                        </a>
                    </li>
                @endforeach
                </ul>
            </div>
            {!! ContentManager::customCode('Blog: after sidebar') !!}
        </div>
    </div>
</div>

@include('partials.footer')

@endsection
