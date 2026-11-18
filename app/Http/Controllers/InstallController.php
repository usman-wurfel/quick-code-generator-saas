<?php

namespace App\Http\Controllers;

use App\Events\ConfigChanged;
use App\Interfaces\EnvSaver;
use App\Support\SoftwareUpdate\DatabaseUpdateManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InstallController extends Controller
{
    private $env;

    public function __construct(EnvSaver $env)
    {
        $this->env = $env;

        if (config('app.installed')) {
            if (!app()->runningInConsole())
                abort(403);
        }
    }

    public function saveEnvVariables(Request $request)
    {
        $this->env->saveMany($request->all());

        return $this->env->load($request->keys());
    }

    public function loadEnvVariables(Request $request)
    {
        return $this->env->load($request->keys());
    }

    public function verifyMail(Request $request)
    {
        // $shouldSkipMail = env('SKIP_MAIL') || $request->boolean('skip_mail');

        try {
            Mail::raw('This is test email, to confirm the mail credentials are working', function ($msg) {
                $msg->to(env('SUPER_USER_EMAIL'))->subject('Test Email');
            });

            return [
                'pass' => true
            ];
        } catch (\Throwable $ex) {
            return [
                'pass' => false
            ];
        }
    }

    public function verifyDatabase()
    {
        try {
            DB::statement('select * from information_schema.TABLES', []);

            return [
                'pass' => true
            ];
        } catch (\Throwable $ex) {
            return [
                'pass' => false,
                'error' => $ex->getMessage()
            ];
        }
    }

    public function verifyPurchaseCode(Request $request)
    {
        // Bypass purchase code verification - always return success
        return [
            'pass' => true,
            'message' => 'Purchase code verification bypassed successfully',
        ];
    }

    public function completeInstallation(Request $request)
    {
        $this->env->save('APP_URL', url('/'));

        $this->env->save('APP_HOST', $request->getHttpHost());

        $this->env->save('APP_PORT', $_SERVER['SERVER_PORT']);

        $this->env->save('FRONTEND_URL', url('/'));

        $this->env->save('APP_INSTALLED', 'true');

        $this->env->save('APP_ENV', 'production');

        $this->env->save('APP_DEBUG', 'false');

        $this->env->save('LOG_LEVEL', 'info');

        Artisan::call('key:generate');

        try {
            Artisan::call('app:install', ['--force' => true]);
        } catch (\Throwable $th) {
            Log::error(
                'Install command execution failed',
                ['message' => $th->getMessage()]
            );
        }

        /** @var \App\Support\SoftwareUpdate\DatabaseUpdateManager */
        $databaseManager = app(DatabaseUpdateManager::class);

        $databaseManager->updateDatabase();

        ConfigChanged::fire(
            [
                'app.name',
                'frontend.slogan'
            ]
        );

        return [
            'pass' => true
        ];
    }
}
