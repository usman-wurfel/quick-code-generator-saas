<?php

namespace App\Plugins\Configs;

use App\Models\Config;
use App\Plugins\BasePlugin;

class ConfigStore
{
    private BasePlugin $plugin;

    public function __construct(BasePlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function serve()
    {
        return collect(

            $this->plugin->configDefs()

        )->map(function (ConfigSection $section) {

            return $this->serveConfigSection($section);
        });
    }

    public function get($key)
    {
        return Config::get($this->key($key));
    }

    public function set($key, $value)
    {
        return Config::set($this->key($key), $value);
    }

    private function serveConfigSection(ConfigSection $section)
    {
        $section->fields = array_map(function (ConfigDef $item) {
            return $this->serveConfigItem($item);
        }, $section->fields);

        return $section;
    }

    private function serveConfigItem(ConfigDef $item)
    {
        $item->value = $this->get($item->key);

        $item->expandedKey = $this->key($item->key);

        return $item->toArray();
    }

    private function key($key)
    {
        return sprintf('%s.%s.%s', 'plugin', $this->plugin->slug(), $key);
    }
}
