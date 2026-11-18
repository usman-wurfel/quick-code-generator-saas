<?php

namespace App\Repositories;

use App\Http\Requests\StoreBlogPostRequest;
use App\Http\Requests\UpdateBlogPostRequest;
use App\Interfaces\BlogPostManager as BlogPostManagerInterface;
use App\Interfaces\TranslationManager;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BlogPostManager implements BlogPostManagerInterface
{
    private ModelSearchBuilder $search;
    private TranslationManager $translations;

    public function __construct(ModelSearchBuilder $search, TranslationManager $translations)
    {
        $this->search = $search;
        $this->translations = $translations;
    }

    public function getLatestPosts($number)
    {
        return BlogPost::orderBy('id', 'desc')->published()->limit($number)->get();
    }

    public function search(Request $request)
    {
        $search = $this->search
            ->init(BlogPost::class, $request, orderByIdOnPaginate: false)
            ->withQuery(function ($query) {
                $query->with('translation');
            })
            ->inColumn('title')
            ->search();

        if (!$request->user()?->permitted('blog-post.list-all')) {

            $search->withQuery(function ($query) {
                $query->published();
                $query->orderBy('published_at', 'desc');
            });

            $this->applyCurrentLanguageRestrictions($search->query());
        }

        return $search->paginate();
    }

    private function applyCurrentLanguageRestrictions(Builder $query)
    {
        if (!$this->translations->multilingualEnabled()) return;

        $translation = $this->translations->getCurrentTranslation();

        $query->where('translation_id', $translation->id);
    }

    public function recentPosts(Request $request, int $number = 5)
    {
        return BlogPost::published()
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc')
            ->take($number)
            ->get();
    }

    public function getPost(BlogPost $post, Request $request)
    {
        $post->load('featured_image');

        $post->featured_image_id = $post->featured_image?->id;

        if ($request->user()?->permitted('blog-post.show-any')) {
            return $post;
        }

        $post = BlogPost::published()->find($post->id);

        if (empty($post)) {
            abort(404);
        }

        return $post;
    }

    public function store(StoreBlogPostRequest $request)
    {
        $post = new BlogPost($request->all());

        $post->user_id = $request->user()->id;

        $post->save();

        return $post;
    }

    public function update(BlogPost $post, UpdateBlogPostRequest $request)
    {
        $post->fill($request->all());

        $post->save();

        return $post;
    }

    public function delete(BlogPost $post)
    {
        $post->delete();

        return $post;
    }

    public function bySlug(string $slug)
    {
        $post = BlogPost::published()->whereSlug($slug)->first();

        if (!$post) {
            abort(404);
        }

        return $post;
    }
}
