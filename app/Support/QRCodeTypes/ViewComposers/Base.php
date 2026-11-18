<?php

namespace App\Support\QRCodeTypes\ViewComposers;

use App\Models\QRCode;
use App\Support\ViewComposers\BaseComposer;
use App\Interfaces\FileManager;
use App\Interfaces\SubscriptionManager;
use App\Models\Config;
use App\Models\File;
use App\Models\QRCodeRedirect;
use App\Models\QRCodeWebPageDesign;
use App\Support\QRCodeTypes\ViewComposers\Components\QRCodeFavicon\Component as QRCodeFaviconComponent;
use App\Support\QRCodeTypes\ViewComposers\Traits\CombinesStylesMethods;
use Throwable;

abstract class Base extends BaseComposer
{
    use CombinesStylesMethods;

    protected QRCode $qrcode;

    protected ?QRCodeWebPageDesign $design;

    protected FileManager $files;

    private static ?QRCode $resolvedQRCode = null;

    private SubscriptionManager $subscriptions;

    public QRCodeFaviconComponent $favicon;

    public static function resolveQRCode(QRCode $qrcode)
    {
        static::$resolvedQRCode = $qrcode;
    }

    private function resolvedQRCode(): ?QRCode
    {
        if (static::$resolvedQRCode) {
            return static::$resolvedQRCode;
        }

        $slug = request()->route('slug');

        $redirect = QRCodeRedirect::where('slug', $slug)->first();

        if (!$redirect) {
            abort(404);
        }

        return $redirect->qrcode;
    }

    public function __construct()
    {
        $this->qrcode = $this->resolvedQRCode();

        $this->design = QRCodeWebPageDesign::where('qrcode_id', $this->qrcode->id)->first();

        $this->files = app(FileManager::class);

        $this->subscriptions = app(SubscriptionManager::class);

        $this->favicon = new QRCodeFaviconComponent($this);
    }

    public static function path(): string
    {
        return 'qrcode.types.' . static::type();
    }

    public abstract static function type();

    protected function templateColors($key)
    {
        return [];
    }

    public function shouldShowPoweredBy()
    {
        $user = $this->qrcode->user;

        $remove = $this->subscriptions->userHasAccessToSubscriptionPlanFeature($user, 'qrcode.remove_powered_by');

        return !$remove;
    }

    public function poweredByName()
    {
        if (!empty(trim($name = Config::get('app.powered_by_name')))) {
            return $name;
        }

        return config('app.name');
    }

    public function designValue($key, $default = null)
    {
        $defaultValue = @$this->templateColors($key) ?? $default;

        if (!$this->design) {
            return @$defaultValue;
        }

        return $this->design->value($key, $defaultValue);
    }

    public function fileUrl($key)
    {
        if (!$this->design) return;

        $id = $this->design->value($key);

        return $this->findFileUrl($id);
    }

    public function findFileUrl($fileId, $default = null)
    {
        if (!$fileId) {
            return $default;
        }

        $file = File::find($fileId);

        if (!$file) {
            return $default;
        }

        return $this->files->url($file);
    }

    public function bg()
    {
        $defaultPath = sprintf(
            '/assets/images/%s/%s/bg.jpg',
            $this::type(),
            $this->getTemplate()
        );

        $mtime = filemtime(base_path("public/$defaultPath"));

        return $this->fileUrl('backgroundImage') ?? asset(
            "$defaultPath?v=$mtime"
        );
    }

    protected function getTemplate()
    {
        return $this->qrcodeData('businessType', 'bakery');
    }

    public function qrcodeData($key, $default = null)
    {
        try {
            $value = $this->qrcode->data->{$key};

            if (empty($value)) {
                return $default;
            }

            return $value;
        } catch (Throwable $th) {
            return $default;
        }
    }

    public function getQRCode()
    {
        return $this->qrcode;
    }

    public function renderFaviconIfNeeded()
    {
        $url = $this->fileUrl('favicon');

        $enabled = $this->designValue('favicon_enabled');

        if (empty($url) || $enabled === 'disabled') return;

        return sprintf('<link rel="icon" href="%s" />', $url);
    }
}
