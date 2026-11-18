<?php

namespace App\Support;


class ArrayHelper
{
    public static function sort(&$array, $sortKey = 'sort_order', $defaultValue = 100)
    {
        usort($array, function ($a, $b) use ($sortKey, $defaultValue) {
            $s1 = @$a[$sortKey] != null ? @$a[$sortKey] : $defaultValue;
            $s2 = @$b[$sortKey] != null ? @$b[$sortKey] : $defaultValue;

            return $s1 - $s2;
        });
    }
}
