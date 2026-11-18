<?php

namespace App\Support;

use App\Events\ShouldSaveQRCodeVariants;
use App\Interfaces\FileManager;
use App\Models\File;
use App\Models\LeadForm;
use App\Models\QRCode;
use App\Models\QRCodeWebPageDesign;
use App\Support\System\Traits\WriteLogs;

class QRCodeCopier
{
    use WriteLogs;

    private FileManager $files;
    private QRCodeWebPageDesignManager $webpageDesignManager;

    public function __construct()
    {
        $this->files = app(FileManager::class);
        $this->webpageDesignManager = app(QRCodeWebPageDesignManager::class);
    }

    public function copy(QRCode $qrcode): QRCode
    {
        $data = $qrcode->toArray();

        $clone = new QRCode($data);

        $clone->user_id = $qrcode->user_id;

        $clone->name = $this->makeCopyName($qrcode);

        $clone->generateFileName();

        QRCodeStorage::ofQRCode($qrcode)->copySvg($clone);

        $clone->save();

        $this->copyFile($qrcode, $clone, 'foreground_image');

        $this->copyFile($qrcode, $clone, 'logo');

        $this->copyWebPageDesign($qrcode, $clone);

        $this->copyLeadForm($qrcode, $clone);

        $clone->refresh();

        event(new ShouldSaveQRCodeVariants($clone));

        return $clone;
    }

    private function copyLeadForm(QRCode $from, QRCode $to)
    {
        $fromDesign = $this->webpageDesignManager->getDesign($from);

        $id = @$fromDesign->design['lead_form_id'];

        if (!$id) {
            return;
        }

        $toDesign = $this->webpageDesignManager->getDesign($to);

        $leadForm = LeadForm::find($id);

        $data = $leadForm->toArray();

        unset($data['id']);

        $clone = new LeadForm;

        $clone->forceFill($data);

        $clone->related_model_id = $toDesign->id;

        $clone->save();

        $designData = $toDesign->design;

        $designData['lead_form_id'] = $clone->id;

        $toDesign->design = $designData;

        $toDesign->save();
    }

    private function copyWebPageDesign(QRCode $from, QRCode $to)
    {
        $webpageDesign = $this->webpageDesignManager->getDesign($from);

        if (!$webpageDesign) return;

        $clone = new QRCodeWebPageDesign();

        $clone->forceFill($webpageDesign->toArray());

        unset($clone->id);

        $clone->qrcode_id = $to->id;

        $clone->save();

        $this->copyWebPageDesignFiles($webpageDesign, $clone);
    }

    private function copyWebPageDesignFiles($original, $clone)
    {
        $fileIds = File::select('id')
            ->where('attachable_id', $original->id)
            ->where('attachable_type', $original::class)
            ->get()
            ->map(fn ($f) => $f->id);

        $cloneDesign = $clone->design;

        array_walk_recursive(
            $cloneDesign,
            function (&$value) use ($fileIds, $clone) {
                $found = $fileIds->first(fn ($id) => $id == $value);

                $file = File::find($found);

                $exists = $file && $this->files->exists($file);

                if ($exists) {
                    $copiedFile = $this->files->duplicate($file);

                    $copiedFile->attachable_id = $clone->id;

                    $copiedFile->save();

                    $value = $copiedFile->id;
                }
            }
        );

        $clone->design = $cloneDesign;

        $clone->save();
    }

    private function copyFile(
        QRCode $original,
        QRCode $clone,
        string $relationName
    ) {
        if (!$original[$relationName]) {
            return null;
        }

        $file = $this->files->duplicate($original[$relationName]);

        $file->attachable_id = $clone->id;

        $file->save();

        return $file;
    }

    private function makeCopyName(QRCode $qrcode)
    {
        $prefix = t('Copy of');

        $number = preg_replace('/[^\d]/', '', $qrcode->name);

        $name = str_replace($number, '', $qrcode->name);

        $name = str_replace($prefix, '', $name);

        $name = trim($name);

        $number = empty($number) ? 1 : $number;

        $makeName = fn ($n) => "$prefix $name $n";

        while (QRCode::whereName($makeName($number))->first()) {
            $number++;
        }

        return $makeName($number);
    }
}
