<?php

namespace App\Support\QRCodeProcessors;

use App\Exceptions\InvalidOrderException;
use App\Interfaces\QRCodeProcessor;
use App\Support\SvgOutput;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use SVG\SVG;

class SvgBuilder extends BaseProcessor implements QRCodeProcessor
{
    protected function shouldProcess(): bool
    {
        return true;
    }

    protected function build()
    {
        return $this->buildSvgQrCode();
    }

    protected function buildSvgQrCode()
    {
        $options = new QROptions([
            'version'             => QRCode::VERSION_AUTO,
            'imageBase64'         => false,
            'eccLevel'            => EccLevel::H,
            'addQuietzone'        => false,
            // if set to true, the light modules won't be rendered
            'imageTransparent'    => false,
            // empty the default value to remove the fill* attributes from the <path> elements
            'markupDark'          => '',
            'markupLight'         => '',
            // draw the modules as circles isntead of squares
            'circleRadius'        => .4,
            'svgViewBoxSize' => $this->output->size,
            'svgPreserveAspectRatio' => 'xMinYMin',
            // connect paths
            'connectPaths'        => true,
            'excludeFromConnect' => $this->excludeFromConnect(),
            // keep modules of thhese types as square
            'keepAsSquare'        => $this->keepAsSquare(),
            'svgDefs'             => $this->makeSvgDefs(),
        ]);

        $qrcode = new QRCode($options);

        $qrcode->addByteSegment($this->data);

        $outputInterface = new SvgOutput(
            $options,
            $qrcode->getMatrix(),
            $this->qrcode
        );

        return $outputInterface->dump();
    }


    protected function validateSvg()
    {
        if (!empty($this->output->svgString)) {
            throw new InvalidOrderException(static::class . ' must be first processor to execute');
        }
    }

    protected function process()
    {
        $this->output->svgString = $this->build();

        $this->output->svg = SVG::fromString($this->output->svgString);
    }

    protected function drawCircularModules()
    {
        if ($this->qrcode->design->module === 'dots') {
            return true;
        }
        return false;
    }

    protected function excludeFromConnect()
    {
        return [
            QRMatrix::M_FINDER,
            QRMatrix::M_FINDER_DOT,
            QRMatrix::M_LOGO,
            QRMatrix::M_ALIGNMENT
        ];
    }

    protected function keepAsSquare()
    {
        return [
            QRMatrix::M_FINDER | QRMatrix::IS_DARK,
            QRMatrix::M_FINDER_DOT
        ];
    }

    protected function makeSvgDefs()
    {
        return $this->generateStyles();
    }

    protected function generateStyles()
    {
        $lightStyles = '.light { fill: none; }';

        $darkStyles = '';

        if ($this->qrcode->design->fillType === 'solid') {
            $darkStyles = '
                .dark.type-data {fill: %s;}
                .dark.type-finder { fill: %s; }
                .dark.type-finder-dot { fill: %s; }
                .dark.type-alignment { fill: %2$s; }
            ';

            $darkStyles = sprintf(
                $darkStyles,
                $this->qrcode->design->foregroundColor,
                $this->qrcode->design->eyeExternalColor,
                $this->qrcode->design->eyeInternalColor
            );
        }

        return sprintf('<style>%s %s</style>', $lightStyles, $darkStyles);
    }
}
