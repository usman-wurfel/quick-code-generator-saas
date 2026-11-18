<?php

namespace App\Policies;

use App\Http\Middleware\ErrorMessageMiddleware;


abstract class BasePolicy
{
    protected function restrictDemo()
    {
        if (app()->environment('demo')) {
            ErrorMessageMiddleware::setMessage(t('This action is disabled in demo.'));

            abort(403);
        }
    }

    protected function fail($message)
    {
        ErrorMessageMiddleware::setMessage($message);
        abort(403);
    }
}
