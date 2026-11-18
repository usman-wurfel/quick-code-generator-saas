<?php

namespace App\Support\ViewComposers;

class HeadConfigsComposer extends BaseComposer
{
    public static function path(): string
    {
        return 'blue.partials.head.configs';
    }

    public function translationFile()
    {
        $translation = $this->translations::loadCurrentTranslationFile();

        if (empty($translation)) {
            $translation = '{}';
        }

        $translation = json_encode(json_decode($translation));

        return $translation;
    }

    public function locale()
    {
        if (!config('app.installed')) {
            return 'en';
        }

        return $this->translations->getCurrentTranslation()->locale;
    }

    public function direction()
    {
        if (!config('app.installed')) {
            return 'ltr';
        }

        return $this->translations->getCurrentTranslation()->direction;
    }

    public function appName()
    {
        return str_replace("'", "\\'", config("app.name"));
    }

    public function slogan()
    {
        return str_replace("'", "\\'", config("frontend.slogan"));
    }

    public function marketplace()
    {
        // Never expose marketplace vendor names (e.g. CodeCanyon) in page HTML.
        $marketplace = strtoupper((string) env('MARKETPLACE', 'CUSTOM'));

        if (in_array($marketplace, ['CODECANYON', 'ENVATO', 'PIECEX', 'THEMEFOREST'], true)) {
            $marketplace = 'CUSTOM';
        }

        return base64_encode($marketplace !== '' ? $marketplace : 'CUSTOM');
    }
}
