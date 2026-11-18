@extends('blog/layout')

@section('title', t('Blog'))

@section('page-title', PageTitle::makeTitle('Blog'))

@section('head')
@parent
<style>
    .post {
        padding: 1rem;
        border-bottom: 1px solid var(--gray-1);
        line-height: 1.8;
    }

    .post .title {
        margin: 0 0 1rem 0;
    }

    .post .title a {
        color: var(--primary-0);
        text-decoration: none;
    }
</style>
@endsection

@section('breadcrumbs')
<a href="/">{{ t('Home') }}</a>
<a href="{{ route('blog') }}">{{ t('Blog') }}</a>
@endsection

@section('content')

@foreach ($posts as $post)
<div class="post">
    <h2 class="title">
        <a href="{{ $post->url }}">
            {{ $post->title }}
        </a>
    </h2>

    <div class="excerpt">
        {{ $post->excerpt }}
    </div>
</div>
@endforeach

{{ $posts->links() }}
@endsection