<!DOCTYPE html>
<html lang="en">

<head>
    @include('blue.partials.head.entrypoint')
</head>

<body class="qrcode-type-{{ $composer::type() }} qrcode-type">
    {!! ContentManager::customCode('Dynamic QR Code: Body Tag - after open.') !!}

    @section('body')
    @show

    {!! ContentManager::customCode('Dynamic QR Code: Body Tag - before close.') !!}
</body>

</html>