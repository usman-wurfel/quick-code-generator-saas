{{-- Dark theme injectors for Lit shadow roots (CustomStyleInjector) --}}
<style class="qrcg-website-banner-custom-style">
    .data-box {
        background-color: #121a2a !important;
        color: #e8f1ff !important;
        border: 1px solid rgba(46, 201, 176, 0.16);
        box-shadow: none !important;
    }
    qrcg-qrcode-designer {
        background: #0d1524 !important;
        color: #e8f1ff !important;
    }
    .advanced-designer-close {
        color: #8b9bb4 !important;
    }
    a { color: #ff5a1f !important; }
    .warning {
        background-color: rgba(255, 90, 31, 0.15) !important;
        color: #e8f1ff !important;
    }
    .disabled-file-input-instructions {
        color: #8b9bb4 !important;
    }
</style>
<style class="qrcg-type-selector-custom-style">
    button {
        color: #c9d7ea !important;
        border-bottom-color: #334155 !important;
    }
    button:focus {
        border-bottom-color: #2ec9b0 !important;
    }
    button:hover,
    button.active {
        background-color: rgba(46, 201, 176, 0.1) !important;
        border-bottom-color: #ff5a1f !important;
        color: #ffffff !important;
    }
    button:active {
        color: #ffffff !important;
    }
</style>
<style class="qrcg-input-custom-style">
    :host {
        color: #e8f1ff;
    }
    input, textarea {
        background: #121a2a !important;
        color: #e8f1ff !important;
        border-color: rgba(46, 201, 176, 0.22) !important;
    }
</style>
<style class="qrcg-qrcode-designer-custom-style">
    :host {
        background: #0d1524 !important;
        color: #e8f1ff !important;
    }
</style>

<section class="website-banner">
    @if ($src = ContentManager::websiteBannerSrc())
    <div class="background user-background" style="background-image: url({{ $src }});"></div>
    @else
    <div class="background"></div>
    @endif

    <div class="layout-box small">
        <div class="tech-chip-row">
            <span class="tech-chip">
                <span class="dot"></span>
                SYSTEM ONLINE // QR ENGINE v2
            </span>
            <span class="tech-chip muted">
                LATENCY &lt; 40ms
            </span>
        </div>

        <h1 class="section-title">
            {!! ContentManager::contentBlocks('Front Page: main title') !!}
        </h1>

        {!! ContentManager::contentBlocks('Front Page: below main title', noParagraph: false) !!}
    </div>

    <div class="layout-box">
        <div class="control tech-console">
            <div class="console-chrome" aria-hidden="true">
                <span></span><span></span><span></span>
                <em>qrcg://generator</em>
            </div>
            <qrcg-website-banner></qrcg-website-banner>
        </div>
    </div>
</section>

<script>
(function () {
    var css = {
        'QRCG-FORM-COMMENT': ':host{background:#1a2436!important;color:#e8f1ff!important;} .usecase,.content{color:#e8f1ff!important;}',
        'QRCG-INPUT': 'input,textarea{background-color:#0d1524!important;color:#e8f1ff!important;border-color:rgba(46,201,176,.28)!important;} input::placeholder,textarea::placeholder{color:#64748b!important;font-weight:500!important;}',
        'QRCG-TEXTAREA': 'textarea{background-color:#0d1524!important;color:#e8f1ff!important;border-color:rgba(46,201,176,.28)!important;}',
        'QRCG-PREVIEW': ':host{background:#121a2a!important;color:#e8f1ff!important;box-shadow:none!important;border:1px solid rgba(46,201,176,.16)!important;}',
        'QRCG-WEBSITE-BANNER': '.data-box{background:#121a2a!important;color:#e8f1ff!important;box-shadow:none!important;border:1px solid rgba(46,201,176,.14)!important;}'
    };
    var watched = new WeakSet();

    function apply(el) {
        var rule = css[el.tagName];
        var root = el.shadowRoot;
        if (!rule || !root || root.querySelector('style[data-forge-dark]')) return;
        var style = document.createElement('style');
        style.setAttribute('data-forge-dark', '');
        style.textContent = rule;
        root.appendChild(style);
    }

    function walk(root) {
        if (!root || !root.querySelectorAll) return;
        root.querySelectorAll('*').forEach(function (el) {
            if (!el.shadowRoot) return;
            apply(el);
            if (!watched.has(el.shadowRoot)) {
                watched.add(el.shadowRoot);
                new MutationObserver(function () { walk(el.shadowRoot); }).observe(el.shadowRoot, {
                    childList: true,
                    subtree: true
                });
            }
            walk(el.shadowRoot);
        });
    }

    function scan() {
        document.querySelectorAll('.website-banner qrcg-website-banner, .website-banner').forEach(function (el) {
            apply(el);
            if (el.shadowRoot && !watched.has(el.shadowRoot)) {
                watched.add(el.shadowRoot);
                new MutationObserver(function () { walk(el.shadowRoot); }).observe(el.shadowRoot, {
                    childList: true,
                    subtree: true
                });
            }
            walk(el.shadowRoot || el);
        });
    }

    scan();
    document.addEventListener('DOMContentLoaded', scan);
    if (window.customElements && customElements.whenDefined) {
        customElements.whenDefined('qrcg-website-banner').then(function () {
            scan();
            setTimeout(scan, 400);
            setTimeout(scan, 1200);
        });
    }
})();
</script>
