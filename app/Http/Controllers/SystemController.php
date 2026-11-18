<?php

namespace App\Http\Controllers;

use App\Interfaces\FileManager;
use App\Models\Config;
use App\Support\SoftwareUpdate\DatabaseUpdateManager;
use App\Support\SystemStatus\SystemStatus;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Throwable;

class SystemController extends Controller
{
    private DatabaseUpdateManager $dbUpdateManager;

    private array $hiddenInDemo = [
        'services.google.api_key',
        'payment_processors.stripe.secret_key',
        'payment_processors.stripe.publisher_key',
        'payment_processors.paypal.client_id',
        'payment_processors.paypal.client_secret',
        'payment_processors.paddle.vendor_id',
        'payment_processors.paddle.auth_code',
        'payment_processors.paddle.public_key',
        'payment_processors.payu-international.pos_id',
        'payment_processors.payu-international.second_key',
        'payment_processors.payu-international.client_id',
        'payment_processors.payu-international.client_secret',
        'payment_processors.razorpay.key_id',
        'payment_processors.razorpay.key_secret',
        'payment_processors.razorpay.webhook_secret',
        'payment_processors.mercadopago.access_token',
        'payment_processors.mercadopago.public_key',
        'payment_processors.payfast.merchant_id',
        'payment_processors.payfast.merchant_key',
        'payment_processors.xendit.secret_key',
        'payment_processors.xendit.public_key',
        'payment_processors.mollie.api_key',
        'payment_processors.mollie.partner_id',
        'payment_processors.mollie.profile_id',
        'payment_processors.paystack.secret_key',
        'payment_processors.paystack.public_key',
        'filesystems.s3.*',
        'payment_processors.2checkout.*',
        'payment_processors.dintero.*',
        'auth-workflow.*',
        'quickqr_art.api_key',
    ];

    public function __construct(DatabaseUpdateManager $dbUpdateManager)
    {
        $this->dbUpdateManager = $dbUpdateManager;
    }

    public function status()
    {
        $status = new SystemStatus();

        return [
            'ok' => $status->ok() && !$this->dbUpdateManager->hasDatabaseUpdate(),
            'entries' => $status->get()
        ];
    }

    public function checkDatabaseUpdate()
    {
        return [
            'update_available' => $this->dbUpdateManager->hasDatabaseUpdate()
        ];
    }

    public function updateDatabase()
    {
        $this->dbUpdateManager->updateDatabase();

        return [
            'updated' => true
        ];
    }

    public function getTimezones()
    {
        return DateTimeZone::listIdentifiers();
    }

    public function cron()
    {
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        ob_start();

        try {
            Artisan::call('schedule:run');
        } catch (Throwable $th) {
        }

        try {
            Artisan::call('queue:work --stop-when-empty');
        } catch (Throwable $th) {
        }

        ob_get_clean();
    }

    /**
     * On some environments there is no frequent cron tasks
     * let's try to execute crons manually.
     * 
     * Default OS cron is prefered over this.
     */
    public function cronRunner()
    {
        set_time_limit(0);
        ignore_user_abort(true);

        ob_start();

        Http::get(url('/system/cron'));

        sleep(60);

        try {
            Http::timeout(1)->get(url('/system/cron/runner'));
        } catch (Throwable $th) {
            //
        }

        ob_get_clean();
    }

    private function shouldRestrictDemo()
    {
        if (env('ALLOW_ADDING_NEW_PAYMENT_PROCESSOR_IN_DEMO')) {
            return false;
        }

        return true;
    }

    public function saveConfigs(Request $request)
    {
        if ($this->shouldRestrictDemo())
            $this->restrictDemo();

        if (!$request->user()->permitted('system.settings')) {
            abort(403);
        }

        foreach ($request->all() as $input) {
            Config::set($input['key'], @$input['value']);
        }

        return array_map(function ($input) {
            return [
                'key' => $input['key'],
                'value' => Config::get($input['key'])
            ];
        }, $request->all());
    }

    public function getConfigs(Request $request)
    {
        if (!$request->user()->permitted('system.settings')) {
            abort(403);
        }

        $keys = explode(',', $request->keys);

        $configsArray = array_map(function ($key) {
            return [
                'key' => $key,
                'value' => Config::get($key) ?? config($key)
            ];
        }, $keys);

        $configsArray = $this->hideInDemo($configsArray, $this->hiddenInDemo);

        return $configsArray;
    }

    public function uploadConfigAttachment(Request $request, FileManager $files)
    {
        $this->restrictDemo();

        $key = $request->key;

        if (!$request->user()->permitted('system.settings')) {
            abort(403);
        }

        if (!Config::id($key)) {
            Config::set($key, null);
        }

        $request->merge([
            'attachable_type' => Config::class,
            'attachable_id' => Config::id($key),
            'type' => FileManager::FILE_TYPE_CONFIG_ATTACHMENT
        ]);

        $file = $files->store($request);

        Config::set($key, $file->id);

        return $file;
    }

    public function testStorage()
    {
        /** @var FileManager */
        $files = app(FileManager::class);

        return [
            'result' => $files->testReadWrite()
        ];
    }

    private function hideInDemo($configsArray, $keysToHide)
    {
        if (env('ALLOW_ADDING_NEW_PAYMENT_PROCESSOR_IN_DEMO')) {
            return $configsArray;
        }

        if (!app()->environment('demo')) {
            return $configsArray;
        }

        return array_map(function ($config) use ($keysToHide) {
            $shouldHide = array_filter(
                $keysToHide,
                fn ($key) => preg_match("/$key/", $config['key'])
            );

            if ($shouldHide) {
                return array_merge(
                    $config,
                    ['value' => 'hidden_in_demo']
                );
            }

            return $config;
        }, $configsArray);
    }
}
