<?php

namespace App\Providers;

use App\Plugins\PluginManager;
use Illuminate\Support\ServiceProvider;

class PluginsServiceProvider extends ServiceProvider
{
    private ?PluginManager $_manager = null;

    private function manager()
    {
        if (!$this->_manager) {
            $this->_manager = new PluginManager();
        }

        return $this->_manager;
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->manager()->register();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->manager()->boot();
    }
}
