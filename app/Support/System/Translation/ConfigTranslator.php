<?php

namespace App\Support\System\Translation;

use App\Interfaces\TranslationManager;
use App\Models\Config;
use App\Models\MetaItem;
use Illuminate\Support\Facades\Log;


/**
 * Translates a given path in any config key
 * because configs are json encoded data and might
 * have nested arrays
 */
class ConfigTranslator
{
    public function saveConfigLine($configKey, $path, $locale, $text)
    {
        $item = Config::where('key', $configKey)->first();

        if (!$item) {
            abort(404);
        }

        return $item->setMeta(
            sprintf(
                'translation-%s-%s',
                $locale,
                $path
            ),
            $text
        );
    }

    public function translateLine($configKey, $path)
    {
        $model = Config::where('key', $configKey)->first();

        if (!$model) {
            return;
        }

        /** @var TranslationManager */
        $translations = app(TranslationManager::class);

        $translation = $translations->getCurrentTranslation();

        $translated = $model->getMeta(
            sprintf('translation-%s-%s', $translation->locale, $path)
        );

        return $translated;
    }

    public function getConfigLines($configKey, $path)
    {
        $configItem = Config::where('key', $configKey)->first();

        $items = MetaItem::where('related_model', $configItem::class)
            ->where('related_model_id', $configItem->id)
            ->where('key', 'like', 'translation%')
            ->get();

        return $items->filter(function ($item) use ($path) {
            return preg_match("/translation-.*-$path/", $item->key);
        })->values();
    }
}
