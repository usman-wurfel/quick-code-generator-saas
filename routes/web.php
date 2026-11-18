<?php

use Illuminate\Support\Facades\Route;

use App\Models\Config;
use Illuminate\Http\Request;
use App\Support\DomainManager;
use App\Support\QRCodeStorage;
use App\Support\SitemapGenerator;
use Illuminate\Support\Facades\Mail;
use App\Support\DashboardAssetsServer;
use App\Support\Auth\Auth0\Auth0Manager;
use App\Http\Controllers\Auth0Controller;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\QRCodeController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\GdprConsentController;
use App\Support\ViewComposers\MainLayoutComposer;
use App\Http\Controllers\QRCodeRedirectController;
use App\Support\PaymentProcessors\PaymentProcessorManager;
use App\Support\QRCodeTypes\ViewComposers\Components\QRCodeFavicon\FileServer as QRCodeFaviconFileServer;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    if (frontend_custom_url()) {
        return redirect(frontend_custom_url());
    }

    if (Config::get('homepage.under-construction') == 'enabled') {
        return view('blue.others.under-construction-page');
    }

    return view('blue.pages.home');
});

Route::get('/account-credit-cart', function () {
    return view('blue.pages.account-credit-cart');
});

Route::get('/language/{locale}', [TranslationController::class, 'changeLanguage']);

Route::get('/dashboard/qrcodes/designer/preview', function () {
    return view('qrcode.designer-preview');
});

Route::get('/{frontend}', function () {
    return view('blue.pages.dashboard');
})->where('frontend', MainLayoutComposer::PATTERN_PWA_ROUTES);

Route::get('/payment/success', [CheckoutController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/thankyou', [CheckoutController::class, 'paymentThankyou'])->name('payment.thankyou');
Route::get('/payment/canceled', [CheckoutController::class, 'paymentCanceled'])->name('payment.canceled');
Route::get('/payment/invalid', [CheckoutController::class, 'paymentInvalid'])->name('payment.invalid');

PaymentProcessorManager::registerWebRoutes();

Route::get('/sitemap.xml', function () {
    return response()->view('sitemap', [
        'urls' => SitemapGenerator::generate()
    ])->header('content-type', 'application/xml; charset="utf8"');
});

Route::get('/blog/post/{post}', fn () => view('blue.pages.post'))->name('post');

Route::get('/blog', fn () => view('blue.pages.blog'))->name('blog');

Route::get('/email/verify/{id}/{hash}', [AccountController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::get('/login', function () {
    return redirect(config('frontend.url') . '/account/login');
})->name('login');

// Keeping this route for backword compatiblity.

QRCodeRedirectController::bindRoutes();

Route::get('/system/cron', [SystemController::class, 'cron'])->name('cron');

Route::get('/api-docs', function () {
    return file_get_contents(resource_path('views/api-docs/api-docs.html'));
});

Route::get('/robots.txt', function () {
    return view('robots');
});

Route::get('/smtp-test', function (Request $request) {

    if (!env('ENABLE_SMTP_TEST')) {
        return abort(404);
    }

    $to = $request->to ?? env('SUPER_USER_EMAIL', 'admin@gmail.com');

    $message = Mail::raw(
        'We have sent this email successfully using SMTP protocol.',
        function ($message) use ($to) {
            $message->to($to);
            $message->subject('SMTP Test');
        }
    );

    return $message->getSymfonySentMessage()->getDebug();
});

Route::get('/' . DomainManager::DOMAIN_CONNECTION_ROUTE, function () {
    $domainManager = new DomainManager();

    return $domainManager->connectionString();
});

Route::get(Auth0Manager::loginUrl(), [Auth0Controller::class, 'login']);

Route::get(Auth0Manager::logoutUrl(), [Auth0Controller::class, 'logout']);

Route::get(Auth0Manager::callbackUrl(), [Auth0Controller::class, 'handleCallback']);

Route::get('/set-cookie-consent', [GdprConsentController::class, 'setCookieConsent']);

QRCodeStorage::registerDirectSvgRoute();

Route::get('/auto-update', function () {
    if (file_exists(public_path('update.php'))) {
        require_once public_path('update.php');
        die;
    } else {
        abort(404, 'Page not found');
    }
});

Route::get(
    QRCodeFaviconFileServer::ROUTE,
    [QRCodeController::class, 'serveFavicon']
);

Route::get(
    'system/cron/runner',
    [SystemController::class, 'cronRunner']
);

DashboardAssetsServer::registerWebRoute();
