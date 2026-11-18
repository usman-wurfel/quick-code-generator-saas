<?php

namespace App\Support\QRCodeTypes;

use App\Models\QRCode;

class Skype extends BaseType
{
    public static function name(): string
    {
        return t('Skype');
    }

    public static function slug(): string
    {
        return 'skype';
    }

    public function rules(): array
    {
        return [
            'skype_name' => 'required',
        ];
    }

    public function makeData(QRCode $qrcode): string
    {
        $vars = [
            'skype_name',
            'type',
        ];

        foreach ($vars as $var) {
            $$var = '';
            if (isset($qrcode->data->$var)) {
                $$var = trim($qrcode->data->$var);
            }
        }

        if (!$type) {
            $type = 'call';
        }

        return "skype:$skype_name?$type";
    }
}
