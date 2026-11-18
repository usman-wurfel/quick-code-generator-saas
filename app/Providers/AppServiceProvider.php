<?php

namespace App\Providers;

use App\Http\Middleware\CustomDomainServer;
use App\Support\AI\UsageManager as AiUsageManager;
use App\Support\Auth\AuthManager;
use App\Support\ConfigValidation\ConfigValidationManager;
use App\Support\ContentManager;
use App\Support\DropletManager;
use App\Support\FaviconManager;
use App\Support\Mail\PHPMailerTransport;
use App\Support\PageTitle;
use App\Support\PaymentProcessors\PaymentProcessorManager;
use App\Support\QRCodeTypes\ViewComposers\Components\QRCodeFavicon\FileServer as QRCodeFaviconFileServer;
use App\Support\QRCodeTypes\ViewComposers\Manager as QRCodeTypeViewComposerManager;
use App\Support\ViewComposers\LeadForm\LeadFormComposerManager;
use App\Support\ViewComposers\ViewComposerManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;


class AppServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        ini_set('memory_limit', config('app.memory_limit', '256M'));

        Schema::defaultStringLength(191);

        $this->forceHttps();

        $this->bindAppUrl();

        $this->bootMacros();

        $this->app->singleton('pageTitle', function ($app) {
            return new PageTitle();
        });

        $this->app->singleton('contentManager', function () {
            return app(ContentManager::class);
        });

        Paginator::defaultView('pagination/default');

        Paginator::defaultSimpleView('pagination/simple-default');

        JsonResource::withoutWrapping();

        Mail::extend('smtp', function () {
            return new PHPMailerTransport();
        });

        FaviconManager::boot();

        ConfigValidationManager::boot();

        ViewComposerManager::boot();

        QRCodeTypeViewComposerManager::boot();

        DropletManager::boot();

        LeadFormComposerManager::boot();

        QRCodeFaviconFileServer::boot();

        AuthManager::boot();

        PaymentProcessorManager::boot();

        // AiUsageManager::boot();
    }

    private function bootMacros()
    {
        foreach (glob(base_path('macros/*.php')) as $file) {
            require_once $file;
        }
    }

    private function forceHttps()
    {
        if (CustomDomainServer::servingCustomDomain()) return;

        if (config('app.force_https')) {
            URL::forceScheme('https');

            $this->app['request']->server->set('HTTPS', true);

            Config::set('app.url', url('/'));
        }
    }

    private function bindAppUrl()
    {
        if (CustomDomainServer::servingCustomDomain()) {
            return;
        }

        if (Config::get('app.url') !== url('/')) {
            Config::set('app.url', url('/'));
        }
    }
}
