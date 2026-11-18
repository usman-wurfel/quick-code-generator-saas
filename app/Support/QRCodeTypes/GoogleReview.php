<?php

namespace App\Support\QRCodeTypes;

use App\Models\Config;
use App\Models\QRCode;
use App\Models\QRCodeRedirect;
use App\Support\QRCodeTypes\Interfaces\HandlesApiCalls;
use App\Support\QRCodeTypes\Interfaces\ShouldImmediatlyRedirectToDestination;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Validation\Validator;
use Throwable;

class GoogleReview extends BaseDynamicType implements ShouldImmediatlyRedirectToDestination, HandlesApiCalls
{
    use WriteLogs;

    public static function slug(): string
    {
        return 'google-review';
    }

    public static function name(): string
    {
        return t('Google Review');
    }

    public function rules(): array
    {
        return [
            'place' => 'required'
        ];
    }

    public function generateName(QRCode $qrcode): string
    {
        try {
            return $this->fetchPlace($qrcode)['name'];
        } catch (Throwable $th) {

            $this->logError('Google API Error ' . $th->getMessage());

            $validator = FacadesValidator::make([], []);

            $validator->after(function (Validator $validator) {
                $validator->errors()->add(
                    'place',
                    t('Cannot communicate with Google API, make sure Places API is enabled with your current credentials.')
                );
            });

            $validator->validate();
        }
    }

    public function makeDestination(QRCode $qrcode): string
    {
        switch ($this->getUrlType($qrcode)) {
            case 'my-business':
                return $this->makeGoogleBusinessPageUrl($qrcode);
            case 'review-list':
                return $this->makeReviewListUrl($qrcode);
            default:
                return $this->makeReviewCollectionUrl($qrcode);
        }
    }

    public function apiEntryPoint(Request $request)
    {
        if ($request->input('method') === 'getGoogleMapsApiKey') {
            return [
                'api_key' => $this->getGoogleMapsApiKey(),
            ];
        }

        return abort(404, 'Method not found');
    }

    private function getGoogleMapsApiKey()
    {
        return Config::get('services.google.api_key');
    }

    private function getUrlType(QRCode $qrcode)
    {
        return @$qrcode->data->url_type;
    }

    private function makeReviewListUrl(QRCode $qrcode)
    {
        return 'https://search.google.com/local/reviews?placeid=' . $this->getPlaceId($qrcode);
    }

    private function makeReviewCollectionUrl(QRCode $qrcode)
    {
        return 'https://search.google.com/local/writereview?placeid=' . $this->getPlaceId($qrcode);
    }

    private function makeGoogleBusinessPageUrl(QRCode $qrcode)
    {
        return $this->getPlaceUrl($qrcode);
    }

    private function getPlaceUrl(QRCode $qrcode)
    {
        return $this->fetchPlace($qrcode)['url'];
    }

    private function fetchPlace(QRCode $qrcode)
    {
        $placeId = $this->getPlaceId($qrcode);

        $baseURl = 'https://maps.googleapis.com/maps/api/place/details/json';

        $baseURl .= sprintf('?place_id=%s&key=%s', $placeId, $this->getGoogleMapsApiKey());

        $data = Http::get($baseURl)->json();

        if (!isset($data['result'])) {
            $this->logError('Invalid Google Places response. ' . json_encode($data, JSON_PRETTY_PRINT));
        }

        return $data['result'];
    }

    private function getPlaceId(QRCode $qrcode)
    {
        return @$qrcode->data->place->place_id;
    }
}
