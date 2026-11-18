<?php

namespace App\Support\QRCodeTypes\ViewComposers\BioLinksBlocks;

use App\Support\QRCodeTypes\ViewComposers\Traits\HasBusinessHours;

class OpeningHoursBlock extends BaseBlock
{
    use HasBusinessHours;

    public static function slug()
    {
        return 'opening-hours';
    }

    protected function designValue($key)
    {
        return null;
    }

    protected function shouldRender(): bool
    {
        return $this->model->notEmpty('openingHours');
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

    private function fetchOpeningHours()
    {
        return array_map(fn ($f) => (object) $f, $this->model->field('openingHours', []));
    }
}
