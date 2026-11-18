<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Config as ConfigModel;
use App\Interfaces\FileManager;
use App\Models\File;
use App\Support\FaviconManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConfigServiceProvider extends ServiceProvider
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

        try {
            $this->bindDatabaseConfigurations();

            $this->bindAppName();

            $this->bindTimezone();
        } catch (\Throwable $th) {
            /**
             * Boot would fail on docker container build.
             * When executing the command @php artisan package:discover --ansi
             * The database connection won't be ready then.
             */
        }

        // binds frontend.header_logo_url
        $this->bindFileUrlConfig('frontend.header_logo');

        // binds frontend.header_logo_inverse_url
        $this->bindFileUrlConfig('frontend.header_logo_inverse');

        // binds frontend.login_logo_url
        $this->bindFileUrlConfig('frontend.login_logo');
    }

    private function bindFileUrlConfig($configKeyOfFileId)
    {
        try {
            $files = app(FileManager::class);

            if ($fileId = Config::get($configKeyOfFileId)) {

                $file = File::find($fileId);

                if ($file) {
                    Config::set(
                        $configKeyOfFileId . '_url',
                        $files->url($file)
                    );
                }
            }
        } catch (Throwable $th) {
            Log::error('Error while in ConfigServiceProvider::bindFileUrlConfig ' . $th->getMessage(), compact('configKeyOfFileId'));
        }
    }

    private function bindDatabaseConfigurations()
    {
        ConfigModel::all()->each(function ($record) {
            if ($record->value)
                Config::set($record->key, $record->value);
        });
    }

    private function bindAppName()
    {
        $name = ConfigModel::get('app.name');

        if (!empty($name)) {
            Config::set('app.name', $name);
        }
    }

    private function bindTimezone()
    {
        $timezone = ConfigModel::get("app.timezone");

        if (!empty($timezone)) {
            Config::set('app.timezone', $timezone);
        }
    }
}
