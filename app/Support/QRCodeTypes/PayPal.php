<?php

namespace App\Support\QRCodeTypes;

use App\Models\QRCode;
use Illuminate\Support\Facades\Log;

class PayPal extends BaseType
{
    public static function name(): string
    {
        return t('PayPal');
    }

    public static function slug(): string
    {
        return 'paypal';
    }

    public function rules(): array
    {
        return [
            'email' => 'required',
            'amount' => 'required',
        ];
    }

    public function makeData(QRCode $qrcode): string
    {
        $vars = [
            'type',
            'email',
            'amount',
            'shipping',
            'tax',
            'item_name',
            'item_id',
            'currency'
        ];

        foreach ($vars as $var) {
            $$var = '';
            if (isset($qrcode->data->$var)) {
                $$var = trim($qrcode->data->$var);
            }
        }

        $data = [
            'cmd' => $type,
            'amount' => $amount,
            'business' => $email,
            'item_name' => $item_name,
            'item_id' => $item_id,
            'currency_code' => isset($currency) ? $currency : 'USD',
            'shipping' => $shipping,
            'tax_rate' => $tax,
        ];

        Log::debug('Currency is ' . $currency);

        $appends = [
            '_xclick' => 'button_subtype=services&bn=PP-BuyNowBF%3Abtn_buynow_LG.gif%3ANonHostedGuest&lc=US&no_note=0',
            '_cart' => 'button_subtype=products&add=1&bn=PP-ShopCartBF%3Abtn_cart_LG.gif%3ANonHostedGuest&lc=US&no_note=0',
            '_donations' => 'bn=PP-DonationsBF%3Abtn_donate_LG.gif%3ANonHostedGuest&lc=US&no_note=0'
        ];

        $url = "https://www.paypal.com/cgi-bin/webscr";

        $query = http_build_query($data);

        $result = "$url?$query&" . $appends[$type];

        return $result;
    }
}
