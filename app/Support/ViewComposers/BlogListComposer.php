<?php

namespace App\Support\ViewComposers;

use App\Interfaces\BlogPostManager;
use App\Interfaces\CurrencyManager;
use App\Interfaces\FileManager;
use App\Models\BlogPost;
use App\Models\SubscriptionPlan;

use Illuminate\View\View;

class BlogListComposer extends BaseComposer
{
    private BlogPostManager $posts;

    public function __construct(BlogPostManager $posts)
    {
        $this->posts = $posts;
    }

    public static function path(): string
    {
        return 'blue.sections.blog-list';
    }

    public function compose(View $view)
    {
        $view->with('posts', $this->posts->search(request()));
    }
}
