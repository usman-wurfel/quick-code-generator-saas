<?php

namespace App\Support\QRCodeTypes\ViewComposers\BioLinksBlocks;

class ImageGridBlock extends BaseBlock
{
    public static function slug()
    {
        return 'image-grid';
    }

    protected function shouldRender(): bool
    {
        return $this->model->notEmpty('items');
    }
}
