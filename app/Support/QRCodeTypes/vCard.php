<?php

namespace App\Support\QRCodeTypes;

use App\Models\QRCode;

class vCard extends BaseType
{
    public static function name(): string
    {
        return t('vCard');
    }

    public static function slug(): string
    {
        return 'vcard';
    }

    public function rules(): array
    {
        return [
            'firstName' => 'required',
            'lastName' => 'required',
        ];
    }

    public function makeData(QRCode $qrcode): string
    {
        $vars = [
            'firstName',
            'lastName',
            'mobile',
            'phone',
            'fax',
            'email',
            'company',
            'job',
            'street',
            'city',
            'zip',
            'state',
            'country',
            'website'
        ];

        $data = [];

        foreach ($vars as $var) {
            $data[$var] = '';

            if (isset($qrcode->data->$var)) {
                $data[$var] = $qrcode->data->$var;
            }
        }

        $vcard = view('qrcode-content.vcard', $data);

        return $vcard->render();
    }

    public function generateName(QRCode $qrcode): string
    {
        return t('vCard of ') . $qrcode->data->firstName;
    }
}
