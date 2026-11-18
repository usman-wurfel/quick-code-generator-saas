<?php

namespace App\Plugins;

use App\Support\System\Traits\WriteLogs;
use Throwable;

class PluginManager
{
    use WriteLogs;

    const ACTION_BIOLINKS_AFTER_LAYOUT = 'Biolinks: After Layout';

    const ACTION_BODY_BEFORE_CLOSE = 'Body: Before Close';

    const FILTER_IMPORT_URL_QRCODE_OPERATION_EXTEND_QRCODE = 'ImportUrlQRCodeOperation: Extend QR Code';

    const FILTER_SHOULD_PROTECT_QRCODE_BY_PINCODE = 'ProtectByPincode: Should Protect';

    private static $actions = [];

    private static $filters = [];

    public function register()
    {
        $this->getEnabledPlugins()->each(function (BasePlugin $instance) {
            try {
                $instance->register();
            } catch (Throwable $th) {
                $this->logError(
                    sprintf(
                        'Error registering plugin %s %s',
                        $instance::class,
                        $th->getMessage()
                    )
                );
            }
        });
    }

    public function boot()
    {
        $this->getEnabledPlugins()->each(function (BasePlugin $instance) {
            try {
                $instance->boot();
            } catch (Throwable $th) {
                $this->logError(
                    sprintf(
                        'Error booting plugin %s %s',
                        $instance::class,
                        $th->getMessage()
                    )
                );
            }
        });
    }

    public function getEnabledPlugins()
    {
        return $this->getInstances()->filter(fn ($plugin) => $plugin->isEnabled());
    }

    public function find($slug)
    {
        return $this->getEnabledPlugins()->first(fn (BasePlugin $plugin) => $plugin->slug() === $slug);
    }

    private function getPluginClasses()
    {
        return collect(glob(__DIR__ . '/*/Plugin.php'))
            ->map(fn ($str) => str_replace(__DIR__, '', $str))
            ->map(fn ($str) => str_replace('/', '\\', $str))
            ->map(fn ($str) => __NAMESPACE__ . $str)
            ->map(fn ($str) => str_replace('.php', '', $str));
    }

    private function getInstances()
    {
        return $this->getPluginClasses()->filter(function ($class) {
            try {
                $instance = app($class);

                if (!($instance instanceof BasePlugin)) {
                    $this->logError("$class is not an instance of BasePlugin");
                    return false;
                }

                return true;
            } catch (Throwable $th) {
                $this->logError("Error creating instance of $class " . $th->getMessage());

                return false;
            }
        })

            ->map(fn ($c) => app($c))->values();
    }

    /**
     * Similar to wordpress action
     */
    public static function doAction()
    {
        $args = func_get_args();

        $actionName = $args[0];

        $rest = [];

        if (count($args) > 1) {
            $rest = array_slice($args, 1);
        }

        $registeredActions = @static::$actions[$actionName];

        if (!is_array($registeredActions)) return;

        return implode('', array_map(fn ($cb) => call_user_func_array($cb, $rest), $registeredActions));
    }

    public static function addAction($name, $callback)
    {
        static::$actions[$name][] = $callback;
    }

    public static function addFilter($name, $callback)
    {
        static::$filters[$name][] = $callback;
    }

    public static function doFilter($name, $value, ...$params)
    {
        if (empty(static::$filters[$name])) return $value;

        $value = array_reduce(
            static::$filters[$name],
            function ($value, $callback) use ($params) {
                return call_user_func_array(
                    $callback,
                    [
                        $value,
                        ...$params
                    ]
                );
            },
            $value
        );

        return $value;
    }
}
