@extends('blog/layout')

@section('page-title', PageTitle::makeTitle($post->title))

@section('title', $post->title)

@section('head')

<meta name="description" content="{{ $post->meta_description }}" />

@parent
<style>
    .post-content {
        padding: 0 1rem;
        line-height: 1.8;
    }

    .post-content a {
        color: var(--primary-0);
    }

    .post-content img {
        max-width: 100%;
    }

    .post-date {
        color: var(--gray-2);
        font-style: italic;
        font-size: .9rem;
    }
</style>
@endsection

@section('breadcrumbs')
	<a href="{{ url('/') }}">{{ t('Home') }}</a>
	<a href="{{ route('blog') }}">{{ t('Blog') }}</a>
	<a href="{{ $post->url }}">{{ $post->title }}</a>
@endsection

@section('below-title')
    <p class="post-date">
        {{ t('Published:') }} {{ $post->published_at->format('Y-m-d') }}
    </p>
@endsection

@section('content')
<div class="post-content">
    {!! $post->html !!}
</div>
@endsection