<?php

namespace App\Support\QRCodeProcessors\AdvancedShapeProcessors;

use App\Interfaces\FileManager;
use App\Support\GoogleFonts;
use App\Support\QRCodeProcessors\BaseProcessor;
use App\Support\TextRenderer\BaseTextRenderer;
use App\Support\TextRenderer\ImagickTextRenderer;
use App\Support\TextRenderer\InkscapeTextRenderer;
use Illuminate\Support\Facades\Log;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use SVG\Nodes\Embedded\SVGImage;
use SVG\Nodes\Structures\SVGStyle;
use SVG\SVG;

abstract class BaseAdvancedShapeProcessor extends BaseProcessor
{
    protected $id;

    protected $originalSvg;

    protected $originalDoc;

    protected FileManager $files;

    const TEXT_SIZE_MAX = 2;

    public function __construct()
    {
        $this->files = app(FileManager::class);
    }

    protected function shouldProcess(): bool
    {
        return false;
    }

    protected function shouldPostProcess()
    {
        return $this->qrcode->design->advancedShape === $this->id;
    }

    protected function loadSvgFile()
    {
        return file_get_contents(
            sprintf('%s/svg/%s.svg', __DIR__, $this->id)
        );
    }

    protected function process()
    {
    }

    protected function postProcess()
    {
        $this->originalSvg = $this->output->svg;

        $this->originalDoc = $this->originalSvg->getDocument();

        $this->svg = SVG::fromString(
            $this->loadSvgFile()
        );

        $this->doc = $this->svg->getDocument();

        $this->output->svg = $this->svg;

        $this->embedDocument();

        $this->renderText();

        $this->renderStyles();

        $this->resizeQrPlaceholder();
    }


    protected function resizeQrPlaceholder()
    {
        if ($this->qrcode->design->shape === 'none') {
            return;
        }

        $placeholder = $this->originalDoc;

        $length = 40;

        $placeholder->setAttribute('width', $placeholder->getAttribute('width') + $length);
        $placeholder->setAttribute('height', $placeholder->getAttribute('height') + $length);
        $placeholder->setAttribute('x', $placeholder->getAttribute('x') - $length / 2);
        $placeholder->setAttribute('y', $placeholder->getAttribute('y') - $length / 2);
    }

    protected function renderStyles()
    {
        $defs = $this->doc->getElementsByTagName('defs')[0];

        $style = new SVGStyle($this->generateStyles());

        $this->addChild($defs, $style);
    }

    protected function generateStyles()
    {
        $background = 'none';

        if ($this->qrcode->design->backgroundEnabled) {
            $background = $this->qrcode->design->backgroundColor;
        }

        return  sprintf(
            '
            .text-background {
                stroke: %1$s;
                fill: %1$s;
            }

            .frame {
                stroke: %1$s;
                fill: %2$s;
            }

            .background {
                fill: %2$s !important;
            }
        ',
            $this->qrcode->design->textBackgroundColor,
            $background
        );
    }

    protected function embedDocument()
    {
        $qrPlaceHolder = $this->doc->getElementById('qrcode_placeholder');

        $width = $qrPlaceHolder->getAttribute('width');

        $height = $qrPlaceHolder->getAttribute('height');

        $this->originalDoc->setAttribute('width', $width);

        $this->originalDoc->setAttribute('height', $height);

        $this->originalDoc->setAttribute('x', $qrPlaceHolder->getAttribute('x'));

        $this->originalDoc->setAttribute('y', $qrPlaceHolder->getAttribute('y'));

        $qrPlaceHolder->getParent()->addChild($this->originalDoc);

        $qrPlaceHolder->getParent()->removeChild($qrPlaceHolder);
    }

    protected function fieldValue($attribute, $field)
    {
        $key = $field . $attribute;

        $value = $this->qrcode->design->{$key};

        return $value;
    }

    protected function renderText(
        $placeholderId = 'text_placeholder',
        $field = '',
        $overrideText = null,
        $overrideTextSize = null,
        $overrideTextColor = null
    ) {
        $textRenderer = BaseTextRenderer::detectSupportedRenderer();

        if ($this->output->renderText) {
            try {
                $text = $textRenderer?->render(
                    $overrideText ?? $this->fieldValue('text', $field),
                    $this->fieldValue('fontFamily', $field),
                    $this->fieldValue('fontVariant', $field),
                    $overrideTextColor ?? $this->fieldValue('textColor', $field)
                );
            } catch (\Throwable $th) {
                Log::error('Error while writing text on image: ' . $th->getMessage());
                Log::error($th->getTraceAsString());
                return;
            }
        }

        // Placeholder
        $ph = $this->doc->getElementById($placeholderId);

        if (!$ph) {
            return;
        }

        $phWidth = $ph->getAttribute('width');

        $phHeight = $ph->getAttribute('height');

        $src = isset($text) ? $this->inlineImagickHref($text) : '';

        $initialMarginX = $phWidth / 10;

        $initialMarginY = $phHeight / 10;

        $textSize = $this->qrcode->design->{sprintf('%stextSize', $field)};

        if ($overrideTextSize !== null) {
            $textSize = $overrideTextSize;
        }

        $marginX = (2 - $textSize) * $initialMarginX;

        $marginY = (2 - $textSize) * $initialMarginY;

        $width = max(0, $phWidth - $marginX * 2);

        $height = max(0, $phHeight - $marginY * 2);

        $img = new SVGImage(
            $src,
            $ph->getAttribute('x') + $marginX,
            $ph->getAttribute('y') + $marginY,
            $width,
            $height
        );

        $img->setAttribute('id', sprintf('%s_text_placeholder', $field));

        $this->addChild(
            $ph->getParent(),
            $img
        );
    }

    protected static function getTextLines()
    {
        return [
            ''
        ];
    }

    public static function defaultTextRelatedValues()
    {
        $generated = [];

        foreach (static::getTextLines() as $field) {
            $generated = array_merge($generated, [
                sprintf('%stext', $field)  => static::defaultInputValue($field, 'text', 'SCAN ME'),
                sprintf('%sfontFamily', $field) => static::defaultInputValue($field, 'fontFamily', 'Raleway'),
                sprintf('%sfontVariant', $field) => static::defaultInputValue($field, 'fontVariant', '900'),
                sprintf('%stextColor', $field) => static::defaultInputValue($field, 'textColor', '#000000'),
                sprintf('%stextBackgroundColor', $field) => static::defaultInputValue($field, 'textBackgroundColor', '#ffffff'),
                sprintf('%stextSize', $field) => static::defaultInputValue($field, 'textSize', '2'),
            ]);
        }

        return $generated;
    }

    public static function defaultValues()
    {
        return static::defaultTextRelatedValues();
    }

    protected static function defaultInputValue($field, $type, $default)
    {
        return $default;
    }

    protected function designValue($key)
    {
        if (!isset($this->qrcode->design->{$key})) {
            return null;
        }

        return  $this->qrcode->design->{$key};
    }

    protected function bindElementFill($id, $colorKey)
    {
        $color = $this->designValue($colorKey);

        if (empty($color)) return;

        $elem = $this->doc->getElementById($id);

        $elem->setStyle('fill', $color);
    }
}
