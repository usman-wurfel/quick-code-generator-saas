<?php

namespace App\Support\Auth\Workflow;

use App\Models\User;
use App\Support\System\Traits\WriteLogs;
use Google_Client;
use Throwable;

class GoogleAuthWorkflow extends BaseWorkflow
{
    use WriteLogs;

    public static function name(): string
    {
        return 'google';
    }

    protected function socialiteConfigs(): array
    {
        return [
            'client_id',
            'client_secret'
        ];
    }
}
