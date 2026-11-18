<?php

namespace App\Support\QRCodeTypes\ViewComposers\BioLinksBlocks;

class ImageBlock extends BaseBlock
{
    public static function slug()
    {
        return 'image';
    }

    protected function shouldRender(): bool
    {
        return $this->model->notEmpty('image');
    }

    protected function textStyles()
    {
        if ($this->model->empty('textColor')) return;

        return sprintf(
            '%s { color: %s; }',
            $this->blockSelector(),
            $this->model->field('textColor')
        );
    }

    protected function borderStyles()
    {
        if ($this->model->empty('borderColor')) return;

        return sprintf(
            '%s.has-border { background-color: %s; }',
            $this->blockSelector(),
            $this->model->field('borderColor')
        );
    }

    public function classString()
    {
        if ($this->model->field('borderEnabled') === 'disabled') {
            return '';
        }

        return 'has-border';
    }
}
