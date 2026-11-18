<?php

namespace App\Support;

use App\Models\Domain;
use App\Models\QRCode;
use App\Models\QRCodeRedirect;
use App\Support\QRCodeTypes\Interfaces\ShouldImmediatlyRedirectToDestination;
use App\Support\QRCodeTypes\QRCodeTypeManager;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QRCodeRedirectManager
{
    private $slugLength = 6;

    public function updateDestinationIfNeeded(QRCode $qrcode)
    {
        if (!$this->isDynamicType($qrcode->type)) return;

        $redirect = QRCodeRedirect::where('qrcode_id', $qrcode->id)->first();

        if (!$redirect) {
            $redirect = $this->makeRedirect($qrcode);
        } else {
            $redirect->destination = $this->makeDestination($qrcode);
            $redirect->save();
        }
    }

    private function isDynamicType($type)
    {
        return (new QRCodeTypeManager)->isDynamic($type);
    }

    public function updateRedirect(QRCodeRedirect $redirect, $data)
    {

        $validator = Validator::make($data, []);


        $validator->after(function ($validator) use ($data, $redirect) {

            if (empty($data['slug'])) {
                return;
            }

            $anotherRedirect = QRCodeRedirect::where('slug', $data['slug'])
                ->where('id', '<>', $redirect->id)->first();

            if ($anotherRedirect)
                $validator->errors()->add('slug', t('Slug is already used.'));
        });

        $validator->after(function ($validator) use ($data) {

            if (empty($data['domain_id'])) return;

            $domain = Domain::find($data['domain_id']);

            if (!$domain) {
                $validator->errors()->add('domain_id', t('Selected domain cannot be found.'));
            }
        });

        $validator->validate();

        if (!empty($data['slug']))
            $redirect->slug = $data['slug'];

        if (empty($data['domain_id'])) {
            $redirect->domain_id = null;
        } else {
            $redirect->domain_id = $data['domain_id'];
        }

        $redirect->save();

        $redirect->refresh();

        return $redirect;
    }

    private function randomSlug()
    {
        return strtolower(Str::random($this->slugLength));
    }

    private function makeDestination($qrcode)
    {
        $type = $qrcode->resolveType();

        if ($type instanceof ShouldImmediatlyRedirectToDestination) {
            return $type->makeDestination($qrcode);
        }

        return 'self';
    }

    private function makeRedirect(QRCode $qrcode)
    {
        $redirect = new QRCodeRedirect();

        $redirect->slug = $this->randomSlug();

        while (QRCodeRedirect::whereSlug($redirect->slug)->first()) {
            $redirect->slug = $this->randomSlug();
        }

        $redirect->qrcode_id = $qrcode->id;

        $redirect->destination = $this->makeDestination($qrcode);

        $redirect->save();

        return $redirect;
    }
}
