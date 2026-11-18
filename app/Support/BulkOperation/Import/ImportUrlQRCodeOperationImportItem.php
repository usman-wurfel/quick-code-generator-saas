<?php

namespace App\Support\BulkOperation\Import;

use App\Models\BulkOperationInstance;
use App\Models\QRCode;
use App\Plugins\PluginManager;
use App\Support\StringHelper;
use App\Support\System\Traits\WriteLogs;

class ImportUrlQRCodeOperationImportItem extends BaseImportItem
{
    use WriteLogs;

    /** 
     * Advanced Shape means Sticker.
     */
    public $id, $url, $name, $pincode, $advancedShape;

    public function __construct()
    {
    }

    protected function init(
        $operationId,
        $id,
        $url,
        $name,
        $pincode = null,
        $advancedShape = null
    ) {
        $this->operationInstanceId = $operationId;

        $this->id = $id;

        $this->url = $url;

        $this->name = $name;

        $this->pincode = $pincode;

        $this->advancedShape = $advancedShape;

        return $this;
    }

    public function fromCsvRow(BulkOperationInstance $operation, array $row): static
    {
        $instance = new static;

        return $instance->init(
            operationId: $operation->id,
            id: @$row[0],
            url: @$row[1],
            name: @$row[2],
            pincode: @$row[3],
            advancedShape: @$row[4],
        );
    }

    public function toQRCode(): QRCode
    {
        $qrcode = QRCode::find($this->id) ?? new QRCode();

        $qrcode->type = 'url';

        $qrcode->name = $this->name;

        // Do not initialize advancedShape and pin code in first save.

        $qrcode->user_id ??= $this->getOperationInstance()->user_id;

        // Start QR Code Data

        $data = $qrcode->data ?? (object)[];

        $data->url = $this->url;

        $qrcode->data = $data;

        // End QR Code Data

        // Start QR Code Design

        $design = $qrcode->design ?? (object)[];

        $design->advancedShape = $this->advancedShape;

        $qrcode->design = $design;

        // End QR Code Design

        return $qrcode;
    }

    public function saveQRCode(): QRCode
    {
        $qrcode = $this->toQRCode();

        $qrcode->save();

        $qrcode->name = $this->substituteVariables($this->name, $qrcode);

        $qrcode->pincode = $this->substituteVariables($this->pincode, $qrcode);

        $design = $qrcode->design ?? (object)[];

        $design->advancedShape = $this->advancedShape;

        $qrcode->design = $design;

        $data = $qrcode->data;

        $data->url = $this->substituteVariables($this->url, $qrcode);

        $qrcode->data = $data;

        $qrcode = PluginManager::doFilter(
            PluginManager::FILTER_IMPORT_URL_QRCODE_OPERATION_EXTEND_QRCODE,
            $qrcode
        );

        $qrcode->save();

        return $qrcode;
    }

    protected function substituteVariables($input, QRCode $qrcode)
    {
        $vars = [
            'QRCODE_ID' => $qrcode->id,
            'QRCODE_SLUG' => $qrcode->redirect->slug,
            'RANDOM_PINCODE' => $this->generateRandomPinCode(),
            'URL_SIGNATURE' => fn ($input) => $this->generateSignature(
                'URL_SIGNATURE',
                $input
            )
        ];

        foreach ($vars as $var => $value) {
            if (is_callable($value))
                $input = $value($input);
            else
                $input = str_replace($var, $value, $input);
        }

        return $input;
    }

    private function generateSignature($pattern, $value)
    {
        if (!preg_match("/$pattern/", $value)) return $value;

        $value = str_replace($pattern, '', $value);

        $secret = env('APP_KEY');

        $signature = hash_hmac('sha256', $value, $secret);

        $value = sprintf('%s&signature=%s', $value, $signature);

        return $value;
    }

    private function generateRandomPinCode()
    {
        return StringHelper::random(5, range(0, 9));
    }

    public function getCsvColumnNames(): array
    {
        return [
            t('ID (Optional)'),
            t('Destination URL (Required)'),
            t('QR Code Name (Required)'),
            t('PIN Code (Optional)'),
            t('Sticker (Optional)'),
        ];
    }
}
