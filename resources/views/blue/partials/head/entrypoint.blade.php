@section('head')

<title>
    @section('title')
    {{ PageTitle::makeTitle() }}
    @show
</title>

@section('meta-description')
@show

<meta charset="UTF-8" />

<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover" />

<base href="{{ config('app.url') }}/" />

<meta name="csrf-token" content="{{ csrf_token() }}">

@include('blue.partials.head.favicon')

@include('blue.partials.head.configs')

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Manrope:wght@400;500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('/assets/forge-theme.css') }}">
<script>
    document.addEventListener('qrcg-dashboard-notice:request-show', function (event) {
        var detail = event.detail || {};
        var message = detail.message;
        var text = '';

        if (typeof message === 'string') {
            text = message;
        } else if (message && message.strings) {
            text = message.strings.join(' ');
        } else if (message != null) {
            text = String(message);
        }

        if (text.indexOf('Some of the functionality might not work') !== -1) {
            event.stopImmediatePropagation();
        }
    }, true);

    (function () {
        var LIGHT = 'rgb(27, 36, 54)';

        function luminance(color) {
            var match = String(color).match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
            if (!match) return null;
            if (match[4] != null && parseFloat(match[4]) === 0) return null;
            var channels = [match[1], match[2], match[3]].map(function (value) {
                value = value / 255;
                return value <= 0.03928 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4);
            });
            return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
        }

        function inMarketing(element) {
            var node = element;
            while (node) {
                if (node.nodeType === 1 && node.matches && node.matches('.website-banner, .website-header, .website-footer, section')) {
                    return true;
                }
                node = node.parentElement || (node.getRootNode && node.getRootNode().host);
            }
            return false;
        }

        function paint(element) {
            if (!element || element.nodeType !== 1 || element === document.body || element === document.documentElement) {
                return;
            }
            if (element.dataset.forgeContrast === '1' || inMarketing(element)) {
                return;
            }

            var style = getComputedStyle(element);
            var background = luminance(style.backgroundColor);
            var foreground = luminance(style.color);

            if (background != null && background > 0.82 && foreground != null && foreground > 0.72) {
                element.style.color = LIGHT;
                element.dataset.forgeContrast = '1';
            }

            if (element.shadowRoot && !element.shadowRoot.__forgeContrast) {
                element.shadowRoot.__forgeContrast = true;
                element.shadowRoot.addEventListener('slotchange', schedule);
                new MutationObserver(schedule).observe(element.shadowRoot, { childList: true, subtree: true });
            }
        }

        function walk(node) {
            if (!node) return;
            var children = node.children ? Array.prototype.slice.call(node.children) : [];
            children.forEach(function (child) {
                paint(child);
                if (child.shadowRoot) walk(child.shadowRoot);
                walk(child);
            });
        }

        var queued = false;
        function schedule() {
            if (queued) return;
            queued = true;
            requestAnimationFrame(function () {
                queued = false;
                walk(document.body);
            });
        }

        if (document.body) schedule();
        document.addEventListener('DOMContentLoaded', schedule);
        new MutationObserver(schedule).observe(document.documentElement, { childList: true, subtree: true });
        window.addEventListener('qrcg-router:location-changed', schedule);
        setTimeout(schedule, 400);
        setTimeout(schedule, 1200);
    })();
</script>

@include('blue.partials.head.blue-assets')

{{-- Dashboard assets section start --}}
@section('dashboard-assets')
@include('blue.partials.head.dashboard-assets')
@include('blue.partials.head.dashboard-styles')
@show
{{-- Dashboard assets section end --}}

{!! ContentManager::renderConfigThemeStyles() !!}

{!! ContentManager::customCode('Head Tag: before close.') !!}

@include('blue.partials.head.dashboard-custom-code')

{{-- end of @section('head') --}}
@show