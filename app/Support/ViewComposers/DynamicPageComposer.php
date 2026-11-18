<?php

namespace App\Support\ViewComposers;

use App\Models\Page;
use Illuminate\View\View;
use Throwable;

class DynamicPageComposer extends BaseComposer
{
    private Page $page;

    public static function path(): string
    {
        return 'blue.pages.dynamic';
    }

    public function renderHtmlContent()
    {
        $content = $this->page->html_content;

        $partials = $this->getPartials($content);

        $content = $this->renderPartials($content, $partials);

        return $content;
    }

    private function renderPartials($content, $partials)
    {
        if (empty($partials)) return $content;

        foreach ($partials as $partial) {
            $content = preg_replace("/\[$partial]/", $this->renderPartial($partial), $content);
        }

        return $content;
    }

    private function renderPartial($path)
    {
        try {
            return call_user_func([view($path), '__toString']);
        } catch (Throwable $th) {

            dd($th->getMessage());
            return $path;
        }
    }

    private function getPartials($content)
    {
        $partials = [];

        preg_replace_callback('/\[(.*)]/', function ($matches) use (&$partials) {

            if (empty($matches)) return;

            if (isset($matches[1]) && !empty($matches[1])) {
                $partials[] = $matches[1];
            }
        }, $content);

        return $partials;
    }



    public function compose(View $view)
    {
        parent::compose($view);

        $data = $view->getData();

        $this->page = $data['page'];
    }
}
