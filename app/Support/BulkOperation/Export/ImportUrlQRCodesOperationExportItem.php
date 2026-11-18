<?php

namespace App\Support\BulkOperation\Export;

use App\Models\QRCode;

class ImportUrlQRCodesOperationExportItem extends BaseExportItem
{
    public $id, $url, $name, $route, $pincode, $advancedShape;

    public function __construct()
    {
    }

    public static function fromQRCode(QRCode $qrcode)
    {
        $instance = new static();

        $instance->id = $qrcode->id;

        $instance->url = $qrcode->data->url;

        $instance->name = $qrcode->name;

        $instance->route = $qrcode->redirect->route;

        $instance->advancedShape = @$qrcode->design->advancedShape;

        $instance->pincode = $qrcode->pincode;

        return $instance;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'name' => $this->name,
            'route' => $this->route,
            'pincode' => $this->pincode,
            'advancedShape' => $this->advancedShape
        ];
    }

    public function getCsvColumnNames(): array
    {
        return [
            t('ID (Optional)'),
            t('Destination URL'),
            t('QR Code Name'),
            t('QR Code Route (Read Only).'),
            t('PIN Code'),
            t('Sticker')
        ];
    }
}
