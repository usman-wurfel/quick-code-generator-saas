<?php

namespace App\Support\QRCodeTypes\ViewComposers\Traits;

use App\Support\Color;

trait GeneratesGradientBackground
{
    public abstract function designValue($key);

    private function gradientBackgroundColorKey()
    {
        return 'backgroundColor';
    }

    private function generateGradientBackground($selector, $colorKey = null)
    {
        if (!$colorKey) {
            $colorKey = $this->gradientBackgroundColorKey();
        }

        $color = $this->designValue($colorKey);

        if (empty($color)) {
            return null;
        }

        $pattern = $selector . ' { background-image: linear-gradient(135deg, %s, %s); }';

        return sprintf($pattern, $color, Color::adjustBrightness($color, -0.35));
    }
}
