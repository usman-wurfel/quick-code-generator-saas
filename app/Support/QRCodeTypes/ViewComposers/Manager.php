<?php

namespace App\Support\QRCodeTypes\ViewComposers;

use App\Support\System\Traits\ClassListLoader;
use Illuminate\Support\Facades\View;

class Manager
{
    use ClassListLoader;

    private function getDir()
    {
        return __DIR__;
    }

    private function getNamespace()
    {
        return __NAMESPACE__;
    }

    public static function boot()
    {
        $manager = new static;

        $classes = $manager->makeInstantiableListOfClasses(__DIR__);

        foreach ($classes as $class) {
            View::composer($class::path(), $class);
        }
    }
}
