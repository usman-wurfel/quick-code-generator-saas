<?php

namespace App\Support\Sms\Drivers;

use Exception;

/** @deprecated */
class RBSoftSMSPaymentGateway extends BaseDriver
{
    public function slug()
    {
        return 'n/a';
    }

    public function doSend(string $to, string $text)
    {
    }
}
