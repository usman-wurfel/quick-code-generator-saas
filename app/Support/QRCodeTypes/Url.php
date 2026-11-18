<?php

namespace App\Support\QRCodeTypes;

use App\Models\QRCode;
use App\Models\QRCodeRedirect;
use App\Support\QRCodeTypes\Interfaces\ShouldImmediatlyRedirectToDestination;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\Validator;

class Url extends BaseDynamicType implements ShouldImmediatlyRedirectToDestination
{
    use WriteLogs;

    public static function name(): string
    {
        return t('Dynamic URL');
    }

    public static function slug(): string
    {
        return 'url';
    }

    public function rules(): array
    {
        return [
            'url' => 'required'
        ];
    }

    protected function extendValidator(Validator $validator)
    {
        $url = @$validator->getData()['url'];

        if (!$url) {
            return;
        }

        if (preg_match('/^mailto:.+|tel:.+$/', $url)) {
            return true;
        }

        $validator->addRules([
            'url' => 'required|url'
        ]);
    }

    public function makeData(QRCode $qrcode): string
    {
        $redirect = QRCodeRedirect::where('qrcode_id', $qrcode->id)->first();

        if (!$redirect) {
            return $qrcode->data->url;
        }

        return $redirect->route;
    }

    public function generateName(QRCode $qrcode): string
    {
        return Str::limit($qrcode->data->url, 50);
    }

    public function makeDestination(QRCode $qrcode): string
    {
        return $qrcode->data->url;
    }
}
