<?php

namespace App\Support\QRCodeTypes\ViewComposers\BioLinksBlocks;

class TitleBlock extends BaseBlock
{
    public static function slug()
    {
        return 'title';
    }

    protected function shouldRender(): bool
    {
        return $this->model->notEmpty('text');
    }

    protected function textColorStyles()
    {
        if ($this->model->empty('textColor')) return;

        return sprintf(
            '%s h2 { color: %s; }',
            $this->blockSelector(),
            $this->model->field('textColor')
        );
    }
}
