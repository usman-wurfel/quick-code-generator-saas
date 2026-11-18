<?php

namespace App\Support\QRCodeTypes\ViewComposers\BioLinksBlocks;

class ParagraphBlock extends BaseBlock
{
    public static function slug()
    {
        return 'paragraph';
    }

    protected function shouldRender(): bool
    {
        return $this->model->notEmpty('text');
    }

    protected function textColorStyles()
    {
        if ($this->model->empty('textColor')) return;

        return sprintf(
            '%s .text { color: %s; }',
            $this->blockSelector(),
            $this->model->field('textColor')
        );
    }

    protected function textAlignStyles()
    {
        if ($this->model->empty('textAlign')) return;

        return sprintf(
            '%s .text { text-align: %s; }',
            $this->blockSelector(),
            $this->model->field('textAlign')
        );
    }

    protected function fontWeightStyles()
    {
        if ($this->model->empty('fontWeight')) return;

        return sprintf(
            '%s .text { font-weight: %s; }',
            $this->blockSelector(),
            $this->model->field('fontWeight')
        );
    }
}
