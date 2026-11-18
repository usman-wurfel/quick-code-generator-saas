@php

$enabledWorkflows = App\Support\Auth\AuthManager::instance()->getEnabledNames();

$aiIsEnabled = !empty(App\Models\Config::get('quickqr_art.api_key'));

@endphp

<script>
    window.CONFIG = {
        'config.app.url': '{!! config( "app.url" ) !!}',
        'config.app.env': '{!! config( "app.env" ) !!}',
        'config.app.name': '{!! $composer->appName() !!}',
        'config.app.marketplace': '{{ $composer->marketplace() }}',
        'config.frontend.slogan': '{!! $composer->slogan() !!}',
        'config.app.frontend_custom_url': '{!! config( "app.frontend_custom_url" ) !!}',
        'config.qrcode.storage_path': '{!! config( "qrcode.storage_path" ) !!}',
        'config.frontend.header_logo_url': '{!! config( "frontend.header_logo_url" ) !!}',
        'config.frontend.header_logo_inverse_url': '{!! config( "frontend.header_logo_inverse_url" ) !!}',
        'config.frontend.login_logo_url': '{!! config( "frontend.login_logo_url" ) !!}',
        'config.paypal.client_id': '{!! config( "paypal.client_id" ) !!}',
        'config.content-manager.positions': '{!! config( "content-manager.positions" ) !!}',
        'config.app.email_verification_after_sign_up': '{!! config( "app.email_verification_after_sign_up" ) !!}',
        'config.qrcode.skip_install_step_1': '{!! config( "qrcode.skip_install_step_1" ) !!}',
        'config.currency': '{!! config( "currency" ) !!}',
        'config.app.after_logout_action': '{!! config( "app.after_logout_action" ) !!}',
        'config.app.wplus_integration_enabled': '{!! config( "app.wplus_integration_enabled" ) !!}',
        'config.app.frontend_links': '{!! config( "app.frontend_links" ) !!}',
        'config.app.dashboard-client-menu': '{!! config( "app.dashboard-client-menu" ) !!}',
        'config.app.new_user_registration': '{!! config( "app.new_user_registration" ) !!}',
        'config.theme.default_scrollbar': '{!! config( "theme.default_scrollbar" ) !!}',
        'config.preview.canvasTextRender': '{!! config( "preview.canvasTextRender" ) !!}',
        'config.app.paid_subscriptions': '{!! config( "app.paid_subscriptions" ) !!}',
        'config.app.available_qrcode_types': '{!! config( "app.available_qrcode_types" ) !!}',
        'config.app.frontend_pricing_plans_url': '{!! config( "app.frontend_pricing_plans_url" ) !!}',
        'config.droplet.is_large': '{!! config( "droplet.is_large" ) !!}',
        'config.app.allow_iframe_embed': '{!! config( "app.allow_iframe_embed" ) !!}',
        'config.app.mobile_number_field': '{!! config( "app.mobile_number_field" ) !!}',
        'config.dashboard.view_stats_link_in_edit_qrcode_page': '{!! config( "dashboard.view_stats_link_in_edit_qrcode_page" ) !!}',
        'config.frontpage.show_customize_design_button': '{!! config( "frontpage.show_customize_design_button" ) !!}',
        'config.dashboard.select_qrcodes_of_currently_logged_in_user_by_default': '{!! config( "dashboard.select_qrcodes_of_currently_logged_in_user_by_default" ) !!}',
        'config.billing.mode': '{!! config("billing.mode") !!}',
        'config.account_credit.dynamic_qrcode_price': '{!! config("account_credit.dynamic_qrcode_price") !!}',
        'config.account_credit.static_qrcode_price': '{!! config("account_credit.static_qrcode_price") !!}',
        'config.dashboard.help_button_in_dashboard_header': '{!! config("dashboard.help_button_in_dashboard_header") !!}',
        'config.auth0.enabled': '{!! config("auth0.enabled") !!}',
        'config.homepage.homepage-generator': '{!! config("homepage.homepage-generator") !!}',
    }

    history.scrollRestoration = 'manual'

    window.QRCG_TRANSLATION = {!! $composer->translationFile() !!}

    window.QRCG_CURRENT_LOCALE = '{{ $composer->locale() }}'

    window.QRCG_DIRECTION = '{{ $composer->direction() }}'

    window.QRCG_BUNDLE_TYPE = 'build';

    window.QRCG_ENABLED_WORKFLOWS = {!! json_encode($enabledWorkflows); !!};

    window.QRCG_AI_IS_ENABLED = {{ $aiIsEnabled ? 'true' : 'false'}};

</script>

{!! PageManager::renderQrTypeConfigsJsVariable() !!}

{!! App\Support\QRCodeTypes\QRCodeTypeManager::renderQrTypesSortOrder() !!}