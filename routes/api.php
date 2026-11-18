<?php

use App\Models\User;
use App\Models\Page;
use App\Models\QRCode;
use App\Models\BlogPost;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AiGeneratorController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\BulkOperationsController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContentBlockController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomCodeController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\DynamicBioLinkBlockController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LeadFormController;
use App\Http\Controllers\LeadFormResponseController;
use App\Http\Controllers\MarkdownController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\QRCodeController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PaymentProcessorController;
use App\Http\Controllers\PluginsController;
use App\Http\Controllers\QRCodeScanController;
use App\Http\Controllers\QRCodeTypeController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\UtilsController;
use App\Http\Middleware\RemoveEmptyFieldsFromArrays;
use App\Models\Contact;
use App\Models\ContentBlock;
use App\Models\Currency;
use App\Models\CustomCode;
use App\Models\Domain;
use App\Models\DynamicBioLinkBlock;
use App\Models\Folder;
use App\Models\Translation;
use App\Support\GoogleFonts;
use App\Support\QRCodeStorage;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('/install')->group(function () {
    Route::post('/save', [InstallController::class, 'saveEnvVariables']);

    Route::post('/load', [InstallController::class, 'loadEnvVariables']);

    Route::post('/verify-database', [InstallController::class, 'verifyDatabase']);

    Route::post('/verify-mail', [InstallController::class, 'verifyMail']);

    Route::post('/verify-purchase-code', [InstallController::class, 'verifyPurchaseCode']);

    Route::post('/complete', [InstallController::class, 'completeInstallation']);
});

Route::post('/login', [AccountController::class, 'login']);

Route::post('/register', [AccountController::class, 'register']);

Route::post('/forgot-password', [AccountController::class, 'forgotPassword']);

Route::post('/reset-password', [AccountController::class, 'resetPassword']);

Route::get('/qrcodes/preview', [QRCodeController::class, 'preview']);

Route::get('/captcha', [CaptchaController::class, 'getCaptcha']);

Route::get('/fonts', function () {
    $fonts = new GoogleFonts();

    return $fonts->listFamilies();
});

Route::post(
    '/account/resend-verification-email',
    [AccountController::class, 'resendVerificationEmail']
)->middleware(['auth:sanctum', 'throttle:6,1']);


Route::get('/myself', [AccountController::class, 'myself'])
    ->middleware(['auth:sanctum']);

Route::get(
    '/subscription-plans',
    [SubscriptionPlanController::class, 'index']
);

Route::get(
    '/subscription-plans/{subscriptionPlan}',
    [SubscriptionPlanController::class, 'show']
);

Route::get(
    '/files/{file:slug}/resource',
    [FilesController::class, 'resource']
);

Route::post(
    '/contacts',
    [
        ContactController::class,
        'store'
    ]
);

Route::get(
    '/payment-processors/{slug}',
    [PaymentProcessorController::class, 'view']
);

Route::get(
    '/translations',
    [TranslationController::class, 'index']
);

Route::prefix('utils')->group(function () {
    Route::get('list-calling-codes', [UtilsController::class, 'listCallingCodes']);

    Route::get('my-calling-code', [UtilsController::class, 'myCallingCode']);
});

Route::get(
    '/bulk-operations/export-csv/{instance}',
    [BulkOperationsController::class, 'exportCsv']
);

Route::get(
    '/bulk-operations/{type}/csv-sample',
    [BulkOperationsController::class, 'csvSample']
);

Route::post(
    '/lead-form-response',
    [LeadFormResponseController::class, 'store']
);

Route::middleware(['auth:sanctum', 'verified', RemoveEmptyFieldsFromArrays::class])->group(function () {

    Route::post('/account/act-as/{user}', [AccountController::class, 'actAs']);

    Route::post('/account/cancel-subscription', [AccountController::class, 'cancelSubscription']);

    Route::get(
        '/qrcodes/count/scans',
        [QRCodeController::class, 'getQRCodeScanCount']
    );

    Route::get(
        '/qrcodes/count',
        [QRCodeController::class, 'getQRCodeCount']
    );

    Route::crud(
        '/qrcodes',
        QRCodeController::class,
        QRCode::class
    );

    Route::post(
        '/qrcodes/archive/{qrcode}',
        [QRCodeController::class, 'archive']
    )->can('archive,qrcode');

    Route::post(
        '/qrcodes/{qrcode}/copy',
        [QRCodeController::class, 'copy']
    )->can('store,qrcode');

    Route::post(
        '/qrcodes/{qrcode}/logo',
        [QRCodeController::class, 'storeLogo']
    );

    Route::post(
        '/qrcodes/{qrcode}/upload-design-file',
        [QRCodeController::class, 'uploadDesignFile']
    );

    Route::get(
        '/qrcodes/{qrcode}/compatible-svg',
        [QRCodeController::class, 'compatibleSVG']
    );

    Route::get(
        QRCodeStorage::SERVE_QRCODE_SVG_FILE_ROUTE,
        [QRCodeController::class, 'serveSvgFile']
    );

    Route::get(
        '/bulk-operations/types',
        [BulkOperationsController::class, 'listOperationTypes']
    );

    Route::post(
        '/bulk-operations/{type}/create',
        [BulkOperationsController::class, 'storeOperation']
    );

    Route::get(
        '/bulk-operations/{type}/instances',
        [BulkOperationsController::class, 'listInstances']
    );

    Route::get(
        '/bulk-operations/instance-results/{instance}',
        [BulkOperationsController::class, 'listInstanceResults']
    );

    Route::post(
        '/bulk-operations/edit-instance-name/{instance}',
        [BulkOperationsController::class, 'editInstanceName']
    );

    Route::get(
        '/qrcodes/{qrcode}/redirect',
        [QRCodeController::class, 'showRedirect']
    );

    Route::post(
        '/qrcodes/{qrcode}/background-image',
        [QRCodeController::class, 'storeForegroundImage']
    );

    Route::get(
        '/qrcodes/{qrcode}/webpage-design',
        [QRCodeController::class, 'getWebPageDesign']
    );

    Route::post(
        '/qrcodes/{qrcode}/webpage-design',
        [QRCodeController::class, 'saveWebPageDesign']
    );

    Route::post(
        '/qrcodes/{qrcode}/webpage-design-file',
        [QRCodeController::class, 'storeWebPageDesignFile']
    );

    Route::post(
        '/qrcodes/{qrcode}/change-user',
        [QRCodeController::class, 'changeQRCodeUser']
    )->can('changeUser', QRCode::class);

    Route::post(
        '/qrcodes/{qrcode}/pincode',
        [QRCodeController::class, 'setPincode']
    )->can('setPincode', 'qrcode');

    Route::post(
        '/users/{user}/invite-sub-user',
        [UsersController::class, 'inviteSubUser']
    )->can('inviteSubUser', 'user');

    Route::get(
        '/users/{user}/sub-users',
        [UsersController::class, 'listSubUsers']
    )->can('listSubUsers', 'user');

    Route::delete(
        '/users/{user}/sub-users/{subUser}',
        [UsersController::class, 'deleteSubUser']
    )->can('deleteSubUser', ['user', 'subUser']);

    Route::post(
        '/users/{user}/change-account-balance',
        [UsersController::class, 'changeAccountBalance']
    )->can('changeAccountBalance', 'user');

    Route::get(
        '/users/{user}/account-balance',
        [UsersController::class, 'getAccountBalance']
    )->can('getAccountBalance', 'user');

    Route::crud(
        '/users',
        UsersController::class,
        User::class
    );

    Route::post(
        '/users/verify-email/{user}',
        [UsersController::class, 'verifyEmail']
    )->can('forceVerifyEmail', 'user');

    Route::crud(
        '/subscription-plans',
        SubscriptionPlanController::class,
        SubscriptionPlan::class,
        except: ['index', 'show']
    );

    Route::post(
        '/subscription-plans/{subscriptionPlan}/duplicate',
        [
            SubscriptionPlanController::class,
            'duplicate'
        ]
    )->can('duplicate', 'subscriptionPlan');

    Route::get(
        '/payment-processors',
        [PaymentProcessorController::class, 'index']
    );

    Route::post(
        '/payment-processors/{processorSlug}/generate-pay-link/{plan}',
        [PaymentProcessorController::class, 'generatePayLink']
    );

    Route::post(
        '/payment-processors/{processorSlug}/create-charge-link/{amount}',
        [PaymentProcessorController::class, 'createChargeLink']
    );

    Route::post(
        '/payment-processors/{processorSlug}/forward/{method}',
        [PaymentProcessorController::class, 'forwardCall']
    );

    Route::post(
        '/payment-processors/{processorSlug}/test-credentials',
        [PaymentProcessorController::class, 'testCredentials']
    );

    Route::post(
        '/payment-processors/{processorSlug}/register-webhook',
        [PaymentProcessorController::class, 'registerWebhook']
    );

    Route::get(
        '/subscriptions/statuses',
        [SubscriptionController::class, 'listStatuses']
    );

    Route::crud(
        '/subscriptions',
        SubscriptionController::class,
        Subscription::class,
        only: ['index', 'show', 'store', 'update']
    );


    Route::get(
        '/transactions',
        [TransactionController::class, 'index']
    )->can('list,App\Models\Transaction');

    Route::post(
        '/transactions/upload-proof-of-payment',
        [TransactionController::class, 'uploadProofOfPayment']
    );

    Route::post(
        '/transactions/offline-transaction',
        [TransactionController::class, 'storeOfflineTransaction']
    );

    Route::post(
        '/transactions/{transaction}/approve',
        [TransactionController::class, 'approveOfflineTransaction']
    )->can('approve,transaction');

    Route::post(
        '/transactions/{transaction}/reject',
        [TransactionController::class, 'rejectOfflineTransaction']
    )->can('reject,transaction');


    Route::get(
        '/qrcode-scans/count',
        [QRCodeScanController::class, 'count']
    );

    Route::crud(
        '/files',
        FilesController::class,
        File::class,
        only: ['show', 'destroy']
    );

    Route::crud(
        '/blog-posts',
        BlogPostController::class,
        BlogPost::class
    );

    Route::post(
        '/blog-posts/{post}/upload-featured-image',
        [BlogPostController::class, 'uploadFeaturedImage']
    );

    Route::post(
        '/markdown',
        MarkdownController::class
    );

    Route::crud(
        '/content-blocks',
        ContentBlockController::class,
        ContentBlock::class
    );

    Route::delete(
        '/content-blocks/of-translation/{translationId}',
        [ContentBlockController::class, 'destroyAllOfTranslation']
    );

    Route::post(
        '/content-blocks/copy/from/{sourceTranslation}/to/{destinationTranslation}',
        [ContentBlockController::class, 'copyContentBlocks']
    );

    Route::crud(
        '/contacts',
        ContactController::class,
        Contact::class,
        except: ['store']
    );

    Route::get(
        '/system/status',
        [SystemController::class, 'status']
    )->can('system.status');

    Route::get(
        '/system/check_database_update',
        [SystemController::class, 'checkDatabaseUpdate']
    )->can('system.status');

    Route::post(
        '/system/update_database',
        [SystemController::class, 'updateDatabase']
    )->can('system.status');

    Route::get(
        '/system/configs',
        [SystemController::class, 'getConfigs']
    );

    Route::post(
        '/system/configs',
        [SystemController::class, 'saveConfigs']
    );

    Route::post(
        '/system/configs/upload',
        [SystemController::class, 'uploadConfigAttachment']
    );

    Route::get(
        '/system/timezones',
        [SystemController::class, 'getTimezones']
    );

    Route::post(
        '/system/test-storage',
        [SystemController::class, 'testStorage']
    )->can('system.status');

    Route::get(
        '/translations/can-auto-translate',
        [TranslationController::class, 'canAutoTranslate']
    );


    Route::post(
        '/translations/lines',
        [TranslationController::class, 'saveLine']
    );

    Route::get(
        '/translations/lines',
        [TranslationController::class, 'getLines']
    );

    Route::post(
        '/translations/config-lines',
        [TranslationController::class, 'saveConfigLine']
    );

    Route::get(
        '/translations/config-lines',
        [TranslationController::class, 'getConfigLines']
    );

    Route::crud(
        '/translations',
        TranslationController::class,
        Translation::class,
        except: ['index']
    );


    Route::post(
        '/translations/{translation}/upload',
        [TranslationController::class, 'upload']
    );

    Route::post(
        '/translations/{translation}/toggle-activate',
        [TranslationController::class, 'toggleActivate']
    );

    Route::post(
        '/translations/{translation}/set-main',
        [TranslationController::class, 'setMain']
    );

    Route::post(
        '/translations/{translation}/auto-translate',
        [TranslationController::class, 'autoTranslate']
    );

    Route::post(
        '/checkout/stripe/{subscription}',
        [CheckoutController::class, 'stripeCheckout']
    );

    Route::post(
        '/checkout/stripe/verify-checkout-session/{sessionId}',
        [CheckoutController::class, 'stripeVerifyCheckoutSession']
    );

    Route::get(
        '/roles',
        [RolesController::class, 'index']
    )->can('list,App\Models\Role');

    Route::crud(
        '/currencies',
        CurrencyController::class,
        Currency::class
    );

    Route::post(
        '/currencies/{currency}/enable',
        [CurrencyController::class, 'enableCurrency']
    )->can('enable,currency');

    Route::get(
        '/custom-codes/positions',
        [CustomCodeController::class, 'getPositions']
    );

    Route::crud(
        '/custom-codes',
        CustomCodeController::class,
        CustomCode::class
    );

    Route::crud(
        '/pages',
        PageController::class,
        Page::class
    );

    Route::get(
        '/domains/usable',
        [DomainController::class, 'usableDomains'],
    );

    Route::get(
        '/domains/my-domains',
        [DomainController::class, 'myDomains']
    );

    Route::crud(
        '/domains',
        DomainController::class,
        Domain::class
    );

    Route::get(
        '/domains/{domain}/check-connectivity',
        [DomainController::class, 'checkConnectivity']
    );

    Route::put(
        '/qrcode/{qrcode}/redirect',
        [QRCodeController::class, 'updateRedirect']
    );

    Route::put(
        '/domains/{domain}/update-status',
        [DomainController::class, 'updateStatus']
    );

    Route::put(
        '/domains/{domain}/update-availability',
        [DomainController::class, 'updateAvailability']
    );

    Route::put(
        '/domains/{domain}/set-default',
        [DomainController::class, 'setDefaultDomain']
    )->can('setDefault,domain');

    Route::get(
        '/qrcodes/{qrcode}/reports/{slug}',
        [QRCodeController::class, 'report']
    )->can('showStats,qrcode');

    Route::get('/folders/{user}', [
        FolderController::class, 'index'
    ])
        ->can('list', [Folder::class, 'user']);

    Route::get('/folders/{user}/{folder}', [
        FolderController::class, 'show',
    ])->can('show', ['user', 'folder']);

    Route::post('/folders/{user}', [
        FolderController::class, 'store',
    ])->can('store', [Folder::class, 'user']);

    Route::put('/folders/{user}/{folder}', [
        FolderController::class, 'store',
    ])->can('update', ['folder', 'user']);

    Route::delete('/folders/{user}/{folder}', [
        FolderController::class, 'destroy',
    ])->can('destroy', ['folder', 'user']);

    Route::post(
        '/qrcode-types/{slug}',
        [QRCodeTypeController::class, 'apiCall']
    );

    Route::post(
        '/dynamic-biolink-blocks/store-file',
        [DynamicBioLinkBlockController::class, 'storeFile']
    )->can('store', DynamicBioLinkBlock::class);

    Route::crud(
        '/dynamic-biolink-blocks',
        DynamicBioLinkBlockController::class,
        DynamicBioLinkBlock::class,
        paramName: 'dynamicBioLinkBlock',
        except: ['index']
    );

    Route::get(
        '/dynamic-biolink-blocks',
        [DynamicBioLinkBlockController::class, 'index']
    );

    Route::get(
        '/lead-forms',
        [LeadFormController::class, 'index']
    )->can('list', 'App\\Models\\LeadForm');

    Route::get(
        '/lead-forms/{leadForm}',
        [LeadFormController::class, 'show']
    )->can('show', 'leadForm');

    Route::post(
        '/lead-forms',
        [LeadFormController::class, 'store']
    )->can('store', 'App\\Models\\LeadForm');

    Route::put(
        '/lead-forms/{leadForm}',
        [LeadFormController::class, 'update']
    )->can('update', 'leadForm');

    Route::get(
        '/lead-forms/{leadForm}/responses',
        [
            LeadFormResponseController::class, 'ofLeadForm'
        ]
    )->can('show', 'leadForm');

    Route::post(
        '/ai/generate/{qrcode}',
        [AiGeneratorController::class, 'generate']
    )->can('update', 'qrcode');

    Route::get(
        '/ai/fetch/{qrcode}',
        [AiGeneratorController::class, 'fetchPrediction']
    )->can('show', 'qrcode');

    Route::get(
        '/plugins/installed',
        [PluginsController::class, 'listInstalledPlugins']
    )->can('plugins.manage');

    Route::get(
        '/plugins/plugin/{slug}',
        [PluginsController::class, 'viewPlugin']
    );
});
