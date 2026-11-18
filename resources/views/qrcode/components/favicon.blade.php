@php

$fav = $composer->favicon;

@endphp

@if ($fav->fileUrl('apple-touch-icon.png'))
<link rel="apple-touch-icon" sizes="180x180" href="{{ $fav->fileUrl('apple-touch-icon.png') }}" />
@endif

@if ($fav->fileUrl('favicon-32x32.png'))
<link rel="icon" type="image/png" sizes="32x32" href="{{ $fav->fileUrl('favicon-32x32.png') }}" />
@endif

@if ($fav->fileUrl('favicon-16x16.png'))
<link rel="icon" type="image/png" sizes="16x16" href="{{ $fav->fileUrl('favicon-16x16.png') }}" />
@endif

<link rel="manifest" href="{{ $fav->staticUrl('site.webmanifest') }}" />

@if ($fav->fileUrl('safari-pinned-tab.svg'))
<link rel="mask-icon" href="{{ $fav->fileUrl('safari-pinned-tab.svg') }}" color="#eee" />
@endif

@if ($fav->fileUrl('favicon.ico'))
<link rel="shortcut icon" href="{{ $fav->fileUrl('favicon.ico') }}" />
@endif

<meta name="msapplication-TileColor" content={!! empty($composer->designValue('browserconfig.tile_color')) ? '"#fff"' :
$composer->designValue('browserconfig.tile_color') !!} />

<meta name="msapplication-config" content="{{ $fav->staticUrl('browserconfig.xml') }}" />

<meta name="theme-color" content="#ffffff" />