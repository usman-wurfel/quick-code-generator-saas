<?php

namespace App\Plugins;

use App\Console\Kernel;
use App\Plugins\Configs\ConfigStore;
use App\Plugins\Configs\ConfigSection;
use App\Providers\RouteServiceProvider;
use App\Support\MimeTypeResolver;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Illuminate\Support\Str;

abstract class BasePlugin
{
    use WriteLogs;

    public ConfigStore $config;

    public function __construct()
    {
        $this->config = new ConfigStore($this);
    }

    public final function register()
    {
        $this->registerPlugin();

        Kernel::addSchedule([$this, 'schedule']);

        RouteServiceProvider::registerApiRoutes([$this, 'registerApiRoutes']);

        RouteServiceProvider::registerWebRoutes([$this, 'registerWebRoutes']);

        $this->registerViewPath();
    }

    public final function boot()
    {
        $this->bootPlugin();
    }

    public function name()
    {
        $kebab = Str::kebab(
            class_basename($this->getNamespace())
        );

        $space = str_replace('-', ' ', $kebab);

        return Str::title($space);
    }

    public function description()
    {
        return '';
    }

    public function tags()
    {
        return [];
    }

    public function shouldShowInPluginsUi()
    {
        return false;
    }

    public function slug()
    {
        return $this->routesPrefix();
    }

    /**
     * @return ConfigSection[]
     */
    public function configDefs()
    {
        return [];
    }

    public function serveConfig()
    {
        return $this->config->serve();
    }

    protected abstract function registerPlugin();

    protected abstract function bootPlugin();

    public final function schedule(Schedule $schedule)
    {
        $this->pluginSchedule($schedule);
    }

    public function isEnabled()
    {
        return false;
    }

    protected function pluginSchedule(Schedule $schedule)
    {
    }

    private function registerViewPath()
    {
        $paths = config('view.paths');

        $path = base_path('app/Plugins/' . basename($this->pluginDir()) . '/views');

        if (!file_exists($path)) return;

        $paths[] = $path;

        config(['view.paths' => $paths]);
    }

    public function registerApiRoutes()
    {
        Route::prefix($this->routesPrefix())->group(function () {
            $this->apiRoutes();
        });
    }

    public function registerWebRoutes()
    {
        Route::prefix($this->routesPrefix())->group(function () {
            $this->webRoutes();

            $this->registerPublicRoutes();
        });
    }

    private function registerPublicRoutes()
    {
        $this->registerCorsPath();

        Route::get('{path}', function (Request $request) {
            return $this->servePublicRoute($request);
        })->where('path', '.*');
    }

    private function registerCorsPath()
    {
        config(['cors.paths' => [
            ...config('cors.paths'),
            $this->routesPrefix() . '/*'
        ]]);
    }

    private function servePublicRoute(Request $request)
    {
        $path = $request->route('path');

        $file = $this->pluginDir() . '/public/' . $path;

        if (!file_exists($file)) {
            $this->logDebugf('file %s not found in %s', $path, $file);

            abort(404);
        }

        return response(file_get_contents($file), 200, [
            'Content-Type' => MimeTypeResolver::resolve($file)
        ], [
            'Access-Control-Allow-Origin' => '*'
        ]);
    }

    private function pluginDir()
    {
        $classInfo = new ReflectionClass($this);

        return dirname($classInfo->getFileName());
    }

    protected function apiRoutes()
    {
    }

    protected function webRoutes()
    {
    }

    protected function getNamespace()
    {
        $reflection_class = new \ReflectionClass(static::class);

        $namespace = $reflection_class->getNamespaceName();

        return $namespace;
    }

    protected function routesPrefix()
    {
        $namespace = $this->getNamespace();

        $prefix = class_basename($namespace);

        $prefix = strtolower($prefix);

        return $prefix;
    }
}
