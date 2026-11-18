<?php

namespace App\Support\QRCodeTypes\ViewComposers\BioLinksBlocks;

class LogoBlock extends BaseBlock
{
    public static function slug()
    {
        return 'logo';
    }

    protected function shouldRender(): bool
    {
        return $this->model->notEmpty('logo');
    }

    protected function textColorStyles()
    {
        if ($this->model->empty('textColor')) return;

        return sprintf(
            '%s .handle { color: %s; }',
            $this->blockSelector(),
            $this->model->field('textColor')
        );
    }
}
