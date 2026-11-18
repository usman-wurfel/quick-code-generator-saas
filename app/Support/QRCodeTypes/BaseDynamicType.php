<?php

namespace App\Support\QRCodeTypes;

use App\Models\QRCode;
use App\Models\QRCodeRedirect;
use App\Support\QRCodeTypes\Interfaces\PincodeProtectedType;
use App\Support\QRCodeTypes\Traits\ProtectByPincode;

abstract class BaseDynamicType extends BaseType  implements PincodeProtectedType
{
    use ProtectByPincode;

    public function isDynamic(): bool
    {
        return true;
    }

    public function makeData(QRCode $qrcode): string
    {
        $redirect = QRCodeRedirect::where('qrcode_id', $qrcode->id)->first();

        return $redirect->route;
    }
}
