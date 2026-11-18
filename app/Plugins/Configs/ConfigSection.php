<?php

namespace App\Plugins\Configs;


class ConfigSection
{
    public $title;

    /**
     * @var ConfigDef[]
     */
    public array $fields;

    public function __construct($title, $fields)
    {
        $this->title = $title;
        $this->fields = $fields;
    }

    public function toArray()
    {
        return (array)$this;
    }
}
