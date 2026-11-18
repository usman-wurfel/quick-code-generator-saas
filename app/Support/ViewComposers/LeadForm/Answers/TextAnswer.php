<?php

namespace App\Support\ViewComposers\LeadForm\Answers;


class TextAnswer extends BaseAnswer
{
    public static function type(): string
    {
        return 'text';
    }
}
