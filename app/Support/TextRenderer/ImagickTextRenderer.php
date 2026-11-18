<?php

namespace App\Support\TextRenderer;

use Imagick;
use ImagickDraw;
use ImagickPixel;

class ImagickTextRenderer extends BaseTextRenderer
{
    public function isSupported()
    {
        return class_exists(Imagick::class);
    }

    protected function write(): ?Imagick
    {
        $text = $this->text;

        $image = new Imagick();

        $draw = new ImagickDraw();

        $fillPixel = new ImagickPixel('transparent');

        $textPixel = new ImagickPixel(
            $this->color
        );

        /* Black text */
        $draw->setFillColor($textPixel);

        /* Font properties */
        $draw->setFont($this->fontFile);

        $draw->setFontSize(140);

        $metrix = $image->queryFontMetrics($draw, $text);

        $w = $metrix['textWidth'];

        $h = $metrix['textHeight'];

        /* New image */
        $image->newImage($w, $h, $fillPixel);

        /* Create text */
        $image->annotateImage(
            $draw,
            0,
            // $h - $h / 4,
            $h - $h / 4,
            0,
            $text
        );

        /* Give image a format */
        $image->setImageFormat('png');

        $image->trimImage(0);

        return $image;
    }
}
