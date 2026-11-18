<?php

namespace App\Support\ViewComposers;

use App\Support\System\Traits\ClassListLoader;
use Illuminate\Support\Facades\View;

class ViewComposerManager
{
    use ClassListLoader;

    public static function boot()
    {
        $manager = new static;

        $classes = $manager->buildClassList();

        foreach ($classes as $class) {
            $templates = explode('|', $class::path());

            foreach ($templates as $template) {
                View::composer($template, $class);
            }
        }
    }

    protected function buildClassList()
    {
        return $this->makeInstantiableListOfClasses(__DIR__);
    }
}
