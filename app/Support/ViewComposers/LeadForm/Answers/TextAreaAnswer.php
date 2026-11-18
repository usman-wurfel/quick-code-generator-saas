<?php

namespace App\Support\ViewComposers\LeadForm\Answers;


class TextAreaAnswer extends BaseAnswer
{
    public static function type(): string
    {
        return 'textarea';
    }
}
