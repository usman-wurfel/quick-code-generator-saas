<?php

namespace App\Support;

use App\Interfaces\BlogPostManager;
use App\Interfaces\FileManager;
use App\Interfaces\TranslationManager;
use App\Models\Config;
use App\Models\ContentBlock;
use App\Models\CustomCode;
use App\Models\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Database\Eloquent\Builder;

class ContentManager
{
    private static TranslationManager $translations;

    private static FileManager $files;

    private function files()
    {
        if (!isset($this::$files)) {
            $this::$files = app(FileManager::class);
        }

        return $this::$files;
    }

    private function translations()
    {
        if (!isset($this::$translations)) {
            $this::$translations = app(TranslationManager::class);
        }

        return $this::$translations;
    }

    public function __construct(BlogPostManager $posts, TranslationManager $translations)
    {
    }

    public function bodyClass($classes = '')
    {
        $path = Str::slug(request()->path()) ?: 'base';

        return "class='path-$path $classes'";
    }

    public function contentBlocks($position, $noParagraph = true, $join = true)
    {
        $this->addPositionIfNeeded($position);

        $content = $this->getBlocksHtml($position, $join);

        if ($noParagraph === true) {
            $content = preg_replace('/<p\>(.*)<\/p\>/', '$1', $content);
        }

        if (is_string($content))

            $content = trim($content);

        return $content;
    }

    private function getBlocksHtml($position, $join)
    {
        $blocks = $this->getBlocks($position)->map(function ($block) {
            return $block->content_html;
        });

        if ($join)
            return $blocks->join("\n");

        return $blocks;
    }

    private function getBlocks($position)
    {
        $query = ContentBlock::where('position', $position);

        $this->applyCurrentLanguageRestrictions($query);

        return $query
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    private function applyCurrentLanguageRestrictions(Builder $query)
    {
        $translation = $this->translations()->getCurrentTranslation();

        $query->where('translation_id', $translation->id);
    }

    public function customCodeTemplate($position)
    {
        return sprintf(
            '<template class="custom-code" position="%s">%s</template>',
            $position,
            base64_encode($this->customCode($position))
        );
    }

    public function customCode($position)
    {
        $this->addPositionIfNeeded($position, 'custom-code-positions');

        $codes = '';

        try {

            $codes = CustomCode::where(
                'position',
                $position
            )
                ->orderBy('sort_order', 'asc')
                ->get()
                ->map(function ($model) {

                    if ($model->language === 'javascript') {
                        return "<script>$model->code</script>";
                    }

                    if ($model->language === 'css') {
                        return "<style>$model->code</style>";
                    }

                    return $model->code;
                })
                ->join("\n\r");
        } catch (Throwable $th) {
            if (config('app.installed'))
                Log::error('Error while rendering custom code. ' . $th->getMessage());
        }

        return $codes;
    }

    private function shouldTryToAddPosition()
    {
        if (app()->environment('local')) return true;

        return config('app.should_build_custom_code_positions', false);
    }

    public function addPositionIfNeeded($position, $configKey = 'positions')
    {
        if (!$this->shouldTryToAddPosition()) return;

        $positions = $this->getPositions($configKey);

        $found = array_filter($positions, fn ($p) => $p == $position);

        if (!$found) {
            $positions[] = $position;

            sort($positions);

            ConfigFileManager::saveJson('content-manager.' . $configKey, $positions);
        }
    }

    private function getPositions($configKey = 'positions')
    {
        if (app()->environment('local') && function_exists('eval')) {
            $code = file_get_contents(
                base_path('config/content-manager.php')
            );

            $code = str_replace('<?php', '', $code);

            $configs = eval($code);

            $positions = $configs[$configKey];
        } else {
            $positions = config('content-manager.' . $configKey, '');
        }

        $positions = json_decode($positions) ?: [];

        sort($positions);

        return $positions;
    }

    public function renderConfigThemeStyles()
    {
        $configKeys = explode(
            ',',
            'theme.primary_0,theme.primary_1,theme.accent_0,theme.accent_1,theme.input_placeholder_font_style'
        );

        $configKeys = [
            'theme.primary_0',
            'theme.primary_1',
            'theme.accent_0',
            'theme.accent_1',
            'theme.login_bg_color_1',
            'theme.login_bg_color_2',
            'theme.input_placeholder_font_style',
            'theme.blue.primary_0',
            'theme.blue.primary_1',
            'theme.blue.accent_0',
            'theme.blue.accent_1',
        ];

        $cssVar = function ($key) {
            $str = str_replace('theme.', '', $key);
            $str = str_replace('blue.', 'blue-', $str);
            $str = str_replace('_', '-', $str);
            return "--$str";
        };

        $styles = array_reduce($configKeys, function ($result, $key) use ($cssVar) {
            if (json_decode(config($key))) {
                $result .= sprintf("%s: %s; \n", $cssVar($key), json_decode(config($key)));
            }

            return $result;
        }, '');

        $styles = trim($styles);

        return "<style> :root { $styles } </style>";
    }

    /**
     * Search if there are any blocks in any position that matches $search
     */
    public function hasAnyBlocks($search)
    {
        try {
            if ($search[0] != '/') {
                $search = "/$search/";
            }

            $positions = collect($this->getPositions())->filter(
                fn ($p) => preg_match($search, $p)
            );

            $query = ContentBlock::whereIn('position', $positions);

            $this->applyCurrentLanguageRestrictions($query);

            $count = $query->count();

            return $count > 0;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return false;
        }
    }

    public function websiteBannerSrc()
    {
        try {
            $fileId = Config::get('appearance.website_banner');

            return $this->files()->url(File::find($fileId));
        } catch (Throwable $th) {
            return null;
        }
    }
}
