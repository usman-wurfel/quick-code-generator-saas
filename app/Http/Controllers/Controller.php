<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ErrorMessageMiddleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function restrictDemo()
    {
        if (app()->environment('demo')) {
            ErrorMessageMiddleware::setMessage(t('This action is disabled in demo.'));

            abort(403);
        }
    }
}
