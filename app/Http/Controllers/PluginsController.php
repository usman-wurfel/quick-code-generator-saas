<?php

namespace App\Http\Controllers;

use App\Plugins\BasePlugin;
use App\Plugins\PluginManager;
use Illuminate\Http\Request;

class PluginsController extends Controller
{
    private PluginManager $plugins;

    public function __construct()
    {
        $this->plugins = new PluginManager();
    }

    public function listInstalledPlugins()
    {
        return $this->plugins
            ->getEnabledPlugins()
            ->filter(function (BasePlugin $plugin) {
                return $plugin->shouldShowInPluginsUi();
            })
            ->map(function (BasePlugin $plugin) {
                return $this->listApiResponse($plugin);
            })->values();
    }

    public function viewPlugin($slug)
    {
        $plugin = $this->plugins->find($slug);

        if (!$plugin) {
            abort(404);
        }

        return $this->singleApiResponse($plugin);
    }

    private function singleApiResponse(BasePlugin $plugin)
    {
        return array_merge(
            $this->listApiResponse($plugin),
            [
                'configs' => $plugin->serveConfig()
            ]
        );
    }

    private function listApiResponse(BasePlugin $plugin)
    {
        return [
            'name' => $plugin->name(),
            'description' => $plugin->description(),
            'tags' => $plugin->tags(),
            'slug' => $plugin->slug(),
        ];
    }
}
