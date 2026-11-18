<?php

namespace App\Support\AI;

use App\Interfaces\FileManager;
use App\Models\File;
use App\Models\QRCode as QRCodeModel;
use App\Support\QRCodeTypes\QRCodeTypeManager;
use App\Support\SvgOutput;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRImagick;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class PNGQRCodeGenerator
{
    private QRCodeModel $model;

    private QRCodeTypeManager $qrcodeTypes;

    private FileManager $files;

    public function __construct(QRCodeModel $model)
    {
        $this->qrcodeTypes = app(QRCodeTypeManager::class);

        $this->model = $model;

        $this->files = app(FileManager::class);
    }

    private function getContent()
    {
        return $this->qrcodeTypes->find(
            $this->model->type
        )->makeData($this->model);
    }

    private function render()
    {
        $options = new QROptions([
            'version'             => QRCode::VERSION_AUTO,
            'outputType'          => QRCode::OUTPUT_IMAGICK,
            'eccLevel'            => EccLevel::H,
            'imagickBG'           => '#FFFFFF',
            'addQuietzone'        => true,
            // if set to true, the light modules won't be rendered
            'imageTransparent'    => false,
            'scale'               => 20,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($this->getContent());
    }

    public function generate()
    {
        $this->deleteOldFile();

        $data = $this->render();

        $file = $this->files->save(
            name: $this->getFileName(),
            type: $this->files::FILE_TYPE_GENERAL_USE_FILE,
            mime_type: 'image/png',
            attachable_type: $this->model::class,
            attachable_id: $this->model->id,
            user_id: $this->model->user_id,
            extension: 'png',
            data: $data
        );

        $this->setPNGFile($file);

        return $this->getPngUrl();
    }

    private function deleteOldFile()
    {
        $file = $this->getPNGFile();

        if ($file) {
            $this->files->delete($file);
        }
    }

    private function getPngUrl()
    {
        $file = $this->getPNGFile();

        return $this->files->url($file);
    }

    private function getPNGFile()
    {
        $fileId = $this->model->getMeta(
            $this->getPNGMetaKey()
        );

        return File::find($fileId);
    }

    private function setPNGFile(File $file)
    {
        return $this->model->setMeta($this->getPNGMetaKey(), $file->id);
    }

    private function getPNGMetaKey()
    {
        return sprintf('%s::png_file_id', static::class);
    }

    private function getFileName()
    {
        return sprintf('tmp-png-file-for-ai-generation-%s.png', $this->model->id);
    }
}
