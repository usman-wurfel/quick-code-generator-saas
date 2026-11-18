<?php

namespace App\Support\QRCodeTypes\ViewComposers\BioLinksBlocks;

use App\Support\QRCodeTypes\ViewComposers\Traits\CombinesStylesMethods;


abstract class BaseBlock
{
    use CombinesStylesMethods;

    protected BlockModel $model;

    public static abstract function slug();

    protected abstract function shouldRender(): bool;

    public static function path(): string
    {
        return sprintf('qrcode.types.biolinks.%s-block', static::slug());
    }

    public function withModel(BlockModel $model)
    {
        $this->model = $model;

        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function render()
    {
        if (!$this->shouldRender()) return;

        return view($this::path(), [
            'model' => $this->model,
            'block' => $this
        ]);
    }

    protected function blockSelector()
    {
        return sprintf('#%s', $this->model->getId());
    }
}
