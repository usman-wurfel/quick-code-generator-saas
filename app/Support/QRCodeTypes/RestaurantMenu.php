<?php

namespace App\Support\QRCodeTypes;

use App\Models\QRCode;
use App\Models\QRCodeRedirect;


class RestaurantMenu extends BaseDynamicType
{
    public static function name(): string
    {
        return t('Restaurant Menu');
    }

    public static function slug(): string
    {
        return 'restaurant-menu';
    }

    public function rules(): array
    {
        return [
            'restaurant_name' => 'required',
            'phone' => 'required',
            'website' => 'url'
        ];
    }

    public function generateName(QRCode $qrcode): string
    {
        return sprintf(
            '%s - %s',
            $qrcode->data->restaurant_name,
            t('Menu')
        );
    }
}
