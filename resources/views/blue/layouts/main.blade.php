<!DOCTYPE html>
<html lang="{{ $composer->locale() }}" dir={{ $composer->direction() }}>

<head>
    @include('blue.partials.head.entrypoint')

    {!! $composer->frontendHeadCustomCode() !!}
</head>

<body {!! ContentManager::bodyClass() !!}>

    {!! ContentManager::customCode('Body Tag: after open.') !!}

    @section('body')
    @show

    {!! ContentManager::customCode('Body Tag: before close.') !!}

    {!! PluginManager::doAction(PluginManager::ACTION_BODY_BEFORE_CLOSE) !!}
</body>

</html>