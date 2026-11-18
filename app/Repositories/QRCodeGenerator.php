<?php

namespace App\Repositories;

use App\Interfaces\QRCodeGenerator as IQRCodeGenerator;
use App\Models\QRCode;
use App\Support\QRCodeOutput;
use App\Support\QRCodeStorage;
use App\Support\QRCodeTypes\QRCodeTypeManager;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class QRCodeGenerator implements IQRCodeGenerator
{
    protected $type;

    protected $data;

    protected $design;

    protected $outputType;

    protected $size;

    protected $model;

    protected static $processors = [];

    protected $renderText = true;

    private QRCodeTypeManager $typeManager;

    public function __construct()
    {
        $this->typeManager = new QRCodeTypeManager();
    }

    public function init(QRCode $model, string $outputType)
    {
        $this->model = $model;
        $this->outputType = $outputType;
        $this->size = config('qrcode.preview_size');
    }

    public function initFromRequest(Request $request)
    {
        if (!empty($request->id)) {
            $model = QRCode::find($request->id);

            if ($model) {
                $this->model = $model;
            }
        }

        if (empty($this->model)) {
            $this->model = new QRCode;
        }

        $this->model->type = $request->type;

        $this->model->data = json_decode(urldecode($request->data));

        $this->model->design = json_decode(urldecode($request->design));

        $this->outputType = $request->outputType;

        $this->size = $request->size ?: config('qrcode.preview_size');

        $this->renderText = $request->boolean('renderText', true);
    }


    public function writeString()
    {
        $output = $this->pipe();

        return $output;
    }

    public static function processor($processor)
    {
        static::$processors[] = $processor;
    }

    public function saveVariants(QRCode $model)
    {
        // png will be saved in browser.
        foreach (['svg'] as $variant) {

            $generator = new static;

            $generator->init($model, $variant);

            QRCodeStorage::ofQRCode($model)->store($generator->writeString());
        }
    }

    public function respondInline()
    {
        $this->outputType = 'svg';

        $content = $this->writeString();

        return QRCodeStorage::serveSvgContent($content);
    }

    protected function pipe()
    {
        $data = $this->typeManager->find($this->model->type)->makeData(
            $this->model
        );

        $output = new QRCodeOutput($this->model, '', $this->size, $data, 0, $this->renderText);

        $pipeline = new Pipeline(app());

        $pipeline->send($output)->through(static::$processors);

        $output = $pipeline->thenReturn();

        $output->round = 1;

        $pipeline->send($output)->through($this::$processors);

        $output = $pipeline->thenReturn();

        return $output;
    }

    protected function makeContentType()
    {
        $contentType = 'image/png';

        if (!empty($this->outputType)) {
            return [
                'svg' => 'image/svg+xml',
                'png' => 'image/png'
            ][$this->outputType];
        }

        return $contentType;
    }
}
