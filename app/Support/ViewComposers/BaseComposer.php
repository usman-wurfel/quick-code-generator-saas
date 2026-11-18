<?php

namespace App\Support\ViewComposers;

use App\Interfaces\TranslationManager;

use Illuminate\View\View;

abstract class BaseComposer
{
    protected TranslationManager $translations;

    protected View $view;

    public function __construct()
    {
        $this->translations = app(TranslationManager::class);
    }

    abstract static function path(): string;

    public function compose(View $view)
    {
        $this->view = $view;

        $view->with('composer', $this);
    }
}
