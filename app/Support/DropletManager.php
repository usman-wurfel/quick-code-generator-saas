<?php

namespace App\Support;

use App\Support\QRCodeTypes\QRCodeTypeManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DropletManager
{
    private ConfigFileManager $configs;

    public function __construct()
    {
        $this->configs = new ConfigFileManager;
    }

    public static function boot()
    {
        $instance = new static;

        QRCodeTypeManager::registerFilter([$instance, 'filterQRCodeTypes']);
    }

    public function verify()
    {
        if (!config('app.installed')) {
            return;
        }

        $code = $this->code();

        $composer = json_decode(
            file_get_contents(
                base_path('composer.json')
            ),
            true
        );

        $version = $composer['version'];

        $name = $composer['name'];

        $http = Http::acceptJson()->timeout(20);

        if (!config('app.http_client_verify_ssl')) {
            $http->withoutVerifying();
        }

        $marketplace = env('MARKETPLACE');

        $url = url('/');

        $requestUrl = base64_decode(
            'aHR0cHM6Ly9xdWlja2NvZGUuZGlnaXRhbC9hcGkvdmVyaWZ5Lw=='
        ) . $code;

        $response = $http->get(
            $requestUrl,
            compact('name', 'version', 'marketplace', 'url')
        );

        $pass = $this->getResponsePass($response);

        if (!$pass) {
            return $this->handleInvalidDroplet();
        }

        $type = $this->getResponseType($response);

        $this->saveType($type);

        $this->saveIsLarge();

        $this->saveDidRun();
    }

    private function getType()
    {
        return config('droplet.type');
    }

    public function isLarge()
    {
        $type = base64_decode($this->getType());

        $pattern = sprintf('/%s/i', base64_decode('ZXh0ZW5kZWQ='));

        return preg_match($pattern, $type) === 1;
    }

    private function saveIsLarge()
    {
        $this->configs->save('droplet.is_large', $this->isLarge());
    }

    public function isSmall()
    {
        return !$this->isLarge();
    }

    public function filterQRCodeTypes(QRCodeTypes\BaseType $type)
    {
        $largeTypes = [
            'YnVzaW5lc3MtcHJvZmlsZQ==', // b-p
            'dmNhcmQtcGx1cw==', // v-p
            'cmVzdGF1cmFudC1tZW51', // r-m
            'cHJvZHVjdC1jYXRhbG9ndWU=', // p-c
            'YmlvbGlua3M=', // b-l
            'bGVhZC1mb3Jt', // l-f
        ];

        $typeIsLarge = array_search(
            base64_encode($type->slug()),
            $largeTypes
        ) !== false;

        if ($typeIsLarge) {
            return $this->isLarge();
        }

        return true;
    }

    private function saveType($type)
    {
        $this->configs->save('droplet.type', base64_encode($type));
    }

    private function saveDidRun()
    {
        if (config('droplet.did_run')) return;

        $this->configs->save('droplet.did_run', true);
    }

    public function didRun()
    {
        if (config('droplet.did_run')) return true;

        return false;
    }

    public function isValid()
    {
        if (empty($this->code())) return false;

        if (config('droplet.is_invalid')) {
            return false;
        }

        return true;
    }

    private function handleInvalidDroplet()
    {
        $this->configs->save('droplet.is_invalid', true);
    }

    private function getResponsePass($response)
    {
        return $response[base64_decode('dmVyaWZpZWQ=')];
    }

    private function getResponseType($response)
    {
        return $response[base64_decode('bGljZW5zZQ==')];
    }

    private function code()
    {
        return config(base64_decode('YXBwLnB1cmNoYXNlX2NvZGU='));
    }
}
