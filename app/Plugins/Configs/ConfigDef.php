<?php

namespace App\Plugins\Configs;


class ConfigDef
{
    public $key, $expandedKey, $title, $type, $instructions, $placeholder, $extra;

    public function __construct(
        $key,
        $title,
        $type,
        $placeholder = null,
        $instructions = null,
        $extra = null,
    ) {
        $this->key = $key;
        $this->title = $title;
        $this->type = $type;
        $this->placeholder = $placeholder;
        $this->instructions = $instructions;
        $this->extra = $extra;
    }

    public function toArray()
    {
        return (array) $this;
    }
}
