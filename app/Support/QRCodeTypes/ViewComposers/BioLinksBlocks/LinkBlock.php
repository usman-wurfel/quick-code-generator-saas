<?php

namespace App\Support\QRCodeTypes\ViewComposers\BioLinksBlocks;

class LinkBlock extends BaseBlock
{
    public static function slug()
    {
        return 'link';
    }

    protected function shouldRender(): bool
    {
        return $this->model->notEmpty('text');
    }

    protected function linkBackgroundColorStyles()
    {
        $backgroundColor = $this->model->field('backgroundColor');

        if (empty($backgroundColor)) {
            return;
        }

        return sprintf('%s a { background-color: %s; border-color: %2$s; }', $this->blockSelector(), $backgroundColor);
    }

    protected function linkTextColorStyles()
    {
        $textColor = $this->model->field('textColor');

        if (empty($textColor)) {
            return;
        }

        return sprintf('%s a { color: %s; }', $this->blockSelector(), $textColor);
    }

    public function linkTarget()
    {
        if ($this->model->empty('target')) return;

        if ($this->model->equals('target', 'self')) return;

        return 'target="_blank"';
    }

    public function html()
    {
        $t = $this->model->field('text');

        $lines = explode("\n", $t);

        return collect($lines)
            ->map(fn ($line) => htmlentities($line))
            ->join('<br>');
    }
}
