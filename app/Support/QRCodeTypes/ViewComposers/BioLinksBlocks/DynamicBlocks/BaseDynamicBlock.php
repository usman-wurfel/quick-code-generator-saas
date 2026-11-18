<?php

namespace App\Support\QRCodeTypes\ViewComposers\BioLinksBlocks\DynamicBlocks;

use App\Interfaces\FileManager;
use App\Models\DynamicBioLinkBlock;
use App\Models\File;
use App\Support\QRCodeTypes\ViewComposers\BioLinksBlocks\BaseBlock;
use Illuminate\Support\Facades\Log;

abstract class BaseDynamicBlock extends BaseBlock
{
    private FileManager $files;

    public abstract static function id();

    public function __construct()
    {
        $this->files = app(FileManager::class);
    }

    public static function dynamicBlock(): ?DynamicBioLinkBlock
    {
        return DynamicBioLinkBlock::find(static::id());
    }

    public static function slug()
    {
        return sprintf('dynamic-%s', static::id());
    }

    public static function name()
    {
        return static::dynamicBlock()->name;
    }

    public static function path(): string
    {
        return sprintf('qrcode.types.biolinks.dynamic-block');
    }

    public function fields()
    {
        $fields = $this->dynamicBlock()->fields;

        if (empty($fields)) return collect([]);

        return collect($fields)->sort(function ($a, $b) {
            return $this->fieldSortOrder($a) - $this->fieldSortOrder($b);
        });
    }

    public function icon($field)
    {
        if (!@$field['icon_id']) {
            return;
        }

        $file = File::find($field['icon_id']);

        if (!$file) return;

        return $this->files->url($file);
    }

    public function fileUrl($field)
    {
        $fileId = $this->value($field);

        $file = File::find($fileId);

        if (!$file) return;

        return $this->files->url($file);
    }

    public function value($field)
    {
        $value = $this->model->field(@$field['name']);

        return $value;
    }

    public function shouldRenderField($field)
    {
        return !empty($this->value($field));
    }

    public function type($field)
    {
        return @$field['type'] ?? 'text';
    }

    public function customCode()
    {
        return $this::dynamicBlock()->custom_code;
    }

    private function fieldSortOrder($field)
    {
        $s = @$field['sort_order'];

        if ($s === null) {
            return 100;
        }

        return $s;
    }

    protected function shouldRender(): bool
    {
        return !empty($this->dynamicBlock());
    }
}
