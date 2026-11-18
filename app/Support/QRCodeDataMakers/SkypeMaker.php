<?php

namespace App\Support\QRCodeDataMakers;

use App\Interfaces\QRCodeDataMaker;


class SkypeMaker extends BaseMaker implements QRCodeDataMaker
{
    protected function verify()
    {
        return true;
    }

    protected function makeData(): string
    {
        $vars = [
            'skype_name',
            'type',
        ];

        foreach ($vars as $var) {
            $$var = '';
            if (isset($this->qrcode->data->$var)) {
                $$var = trim($this->qrcode->data->$var);
            }
        }

        if (!$type) {
            $type = 'call';
        }

        return "skype:$skype_name?$type";
    }
}
