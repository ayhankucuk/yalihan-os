<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Actions\Admin\Blog\DeleteBlogCategoryAction;
use App\Actions\Admin\Blog\DeleteBlogPostAction;
use App\Actions\Admin\Blog\DeleteBlogTagAction;
use App\Actions\Admin\Blog\SetBlogPostFeaturedStateAction;
use App\Actions\Admin\Blog\SetBlogPostPublishStateAction;
use App\Actions\Admin\Blog\SetBlogPostStickyStateAction;
use App\Actions\Admin\Blog\ToggleBlogCategoryAction;
use App\Actions\Admin\Blog\ToggleBlogTagAction;
use App\Actions\Admin\Blog\UpdateBlogCategoryAction;
use App\Actions\Admin\Blog\UpdateBlogPostAction;
use App\Actions\Admin\Blog\UpdateBlogTagAction;
use App\Models\BlogComment;
use App\Services\Admin\AdminSettingsCacheService;
use App\Services\Cache\ControllerCacheMutationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BlogController extends AdminController
{
    public function __construct(
        private readonly ControllerCacheMutationService $cacheMutationService,
        private readonly UpdateBlogCategoryAction $updateBlogCategoryAction,
        private readonly DeleteBlogCategoryAction $deleteBlogCategoryAction,
        private readonly ToggleBlogCategoryAction $toggleBlogCategoryAction,
        private readonly UpdateBlogTagAction $updateBlogTagAction,
        private readonly DeleteBlogTagAction $deleteBlogTagAction,
        private readonly ToggleBlogTagAction $toggleBlogTagAction,
        private readonly UpdateBlogPostAction $updateBlogPostAction,
        private readonly DeleteBlogPostAction $deleteBlogPostAction,
        private readonly SetBlogPostPublishStateAction $setBlogPostPublishStateAction,
        private readonly SetBlogPostFeaturedStateAction $setBlogPostFeaturedStateAction,
        private readonly SetBlogPostStickyStateAction $setBlogPostStickyStateAction,
    ) {
        parent::__construct();
        $this->middleware('can:manage-blog');
    }

    public function index()
    {
        return redirect()->route('admin.blog.comments.index');
    }

    public function comments(Request $request)
    {
        $yayin_durumu = $request->get('yayin_durumu');
        $query = BlogComment::with(['post:id,title,slug', 'user:id,name,email'])->orderByDesc('created_at'); // context7-ignore
        if ($yayin_durumu) {
            $query->where('yayin_durumu', $yayin_durumu);
        }
        $comments = $query->paginate(20)->withQueryString();

        // ✅ SAB: View için gerekli değişkenler
        // ✅ N+1 FIX: Select optimization for posts dropdown
        $posts = \App\Models\BlogPost::select(['id', 'title', 'slug'])
            ->orderBy('title') // context7-ignore
            ->get();

        // ✅ CACHE: İstatistikler cache ile optimize et (1800s = 30 dakika)
        $stats = Cache::remember('blog_comments_stats', 1800, function () {
            return [
                'approved' => BlogComment::where('yayin_durumu', 'approved')->count(),
                'pending' => BlogComment::where('yayin_durumu', 'pending')->count(),
                'rejected' => BlogComment::where('yayin_durumu', 'rejected')->count(),
                'spam' => BlogComment::where('yayin_durumu', 'spam')->count(),
            ];
        });

        return view('admin.blog.comments.index', compact('comments', 'posts', 'stats'));
    }

    public function approveComment(BlogComment $comment)
    {
        $comment->approve(Auth::id());
        // ✅ CACHE INVALIDATION: İstatistik cache'ini temizle
        app(AdminSettingsCacheService::class)->invalidateBlogComments();

        return response()->json(['success' => true]);
    }

    public function rejectComment(Request $request, BlogComment $comment)
    {
        $reason = $request->input('reason');
        $comment->reject(Auth::id(), $reason);
        // ✅ CACHE INVALIDATION: İstatistik cache'ini temizle
        app(AdminSettingsCacheService::class)->invalidateBlogComments();

        return response()->json(['success' => true]);
    }

    public function markCommentAsSpam(Request $request, BlogComment $comment)
    {
        $reason = $request->input('reason');
        $comment->markAsSpam(Auth::id(), $reason);
        // ✅ CACHE INVALIDATION: İstatistik cache'ini temizle
        app(AdminSettingsCacheService::class)->invalidateBlogComments();

        return response()->json(['success' => true]);
    }

    public function categories(Request $request)
    {
        $categories = \App\Models\BlogCategory::withCount('posts')->orderBy('name')->paginate(20); // context7-ignore

        // ✅ CACHE: İstatistikler cache ile optimize et (3600s = 1 saat)
        $istatistikler = Cache::remember('blog_categories_stats', 3600, function () {
            return [
                'toplam' => \App\Models\BlogCategory::count(),
                'aktif' => \App\Models\BlogCategory::where('aktiflik_durumu', true)->count(), // ✅ SAB: bool value
            ];
        });

        return view('admin.blog.categories.index', compact('categories', 'istatistikler'));
    }

    public function tags(Request $request)
    {
        $tags = \App\Models\BlogTag::withCount('posts')->orderBy('name')->paginate(20); // context7-ignore

        // ✅ CACHE: İstatistikler cache ile optimize et (3600s = 1 saat)
        $istatistikler = Cache::remember('blog_tags_stats', 3600, function () {
            return [
                'toplam' => \App\Models\BlogTag::count(),
                'aktif' => \App\Models\BlogTag::where('aktiflik_durumu', true)->count(), // ✅ SAB: bool value
            ];
        });

        return view('admin.blog.tags.index', compact('tags', 'istatistikler'));
    }

    public function posts(Request $request)
    {
        // ✅ N+1 FIX: Eager loading with select optimization
        $posts = \App\Models\BlogPost::with([
            'category:id,name,slug',
            'author:id,name,email',
        ])
            ->select(['id', 'title', 'slug', 'yayin_durumu', 'category_id', 'author_id', 'created_at', 'updated_at'])
            ->latest()
            ->paginate(20);

        // ✅ CACHE: İstatistikler cache ile optimize et (1800s = 30 dakika)
        $istatistikler = Cache::remember('blog_posts_stats', 1800, function () {
            return [
                'toplam' => \App\Models\BlogPost::count(),
                'yayinlanan' => \App\Models\BlogPost::where('yayinlandi', true)->count(),
                'taslak' => \App\Models\BlogPost::where('yayinlandi', false)->count(),
            ];
        });

        // ✅ SAB: View için gerekli değişkenler
        $durum = $request->get('aktiflik_durumu'); // Filter için
        $taslak = $istatistikler['taslak']; // View'da kullanılıyor

        return view('admin.blog.posts.index', compact('posts', 'istatistikler', 'aktiflik_durumu', 'taslak'));
    }

    public function analytics()
    {
        $data = [
            'posts' => \App\Models\BlogPost::count(),
            'categories' => \App\Models\BlogCategory::count(),
            'tags' => \App\Models\BlogTag::count(),
            'comments' => BlogComment::count(),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function clearSidebarCache()
    {
        $this->cacheMutationService->forget('blog_comments_stats');
        $this->cacheMutationService->forget('blog_categories_stats');
        $this->cacheMutationService->forget('blog_tags_stats');
        $this->cacheMutationService->forget('blog_posts_stats');

        return response()->json(['success' => true]);
    }

    public function createCategory()
    {
        return view('admin.blog.categories.create');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'aktiflik_durumu' => 'nullable|boolean',
        ]);

        \App\Models\BlogCategory::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['name']),
            'aktiflik_durumu' => (bool) ($validated['aktiflik_durumu'] ?? true),
        ]);

        return redirect()->route('admin.blog.categories.index');
    }

    public function editCategory($category)
    {
        $categoryModel = \App\Models\BlogCategory::findOrFail($this->resolveRouteParamId($category));

        return view('admin.blog.categories.edit', ['category' => $categoryModel]);
    }

    public function updateCategory(Request $request, $category)
    {
        $categoryModel = \App\Models\BlogCategory::findOrFail($this->resolveRouteParamId($category));
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'aktiflik_durumu' => 'nullable|boolean',
        ]);

        $this->updateBlogCategoryAction->handle($categoryModel, [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['name']),
            'aktiflik_durumu' => (bool) ($validated['aktiflik_durumu'] ?? $categoryModel->aktiflik_durumu),
        ]);

        return redirect()->route('admin.blog.categories.index');
    }

    public function destroyCategory($category)
    {
        $categoryModel = \App\Models\BlogCategory::findOrFail($this->resolveRouteParamId($category));
        $this->deleteBlogCategoryAction->handle($categoryModel);

        return redirect()->route('admin.blog.categories.index');
    }

    public function toggleCategory($category)
    {
        $categoryModel = \App\Models\BlogCategory::findOrFail($this->resolveRouteParamId($category));
        $categoryModel = $this->toggleBlogCategoryAction->handle($categoryModel);

        return response()->json(['success' => true, 'aktiflik_durumu' => (bool) $categoryModel->aktiflik_durumu]);
    }

    public function createTag()
    {
        return view('admin.blog.tags.create');
    }

    public function storeTag(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'aktiflik_durumu' => 'nullable|boolean',
        ]);

        \App\Models\BlogTag::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['name']),
            'aktiflik_durumu' => (bool) ($validated['aktiflik_durumu'] ?? true),
        ]);

        return redirect()->route('admin.blog.tags.index');
    }

    public function editTag($tag)
    {
        $tagModel = \App\Models\BlogTag::findOrFail($this->resolveRouteParamId($tag));

        return view('admin.blog.tags.edit', ['tag' => $tagModel]);
    }

    public function updateTag(Request $request, $tag)
    {
        $tagModel = \App\Models\BlogTag::findOrFail($this->resolveRouteParamId($tag));
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'aktiflik_durumu' => 'nullable|boolean',
        ]);

        $this->updateBlogTagAction->handle($tagModel, [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['name']),
            'aktiflik_durumu' => (bool) ($validated['aktiflik_durumu'] ?? $tagModel->aktiflik_durumu),
        ]);

        return redirect()->route('admin.blog.tags.index');
    }

    public function destroyTag($tag)
    {
        $tagModel = \App\Models\BlogTag::findOrFail($this->resolveRouteParamId($tag));
        $this->deleteBlogTagAction->handle($tagModel);

        return redirect()->route('admin.blog.tags.index');
    }

    public function toggleTag($tag)
    {
        $tagModel = \App\Models\BlogTag::findOrFail($this->resolveRouteParamId($tag));
        $tagModel = $this->toggleBlogTagAction->handle($tagModel);

        return response()->json(['success' => true, 'aktiflik_durumu' => (bool) $tagModel->aktiflik_durumu]);
    }

    public function create()
    {
        return view('admin.blog.posts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'category_id' => 'nullable|integer',
        ]);

        $post = \App\Models\BlogPost::create([
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['title']),
            'content' => $validated['content'] ?? '',
            'category_id' => $validated['category_id'] ?? null,
            'author_id' => Auth::id(),
            'yayinlandi' => false,
        ]);

        return redirect()->route('admin.blog.posts.edit', ['post' => $post->id]);
    }

    public function show($post)
    {
        $postModel = \App\Models\BlogPost::findOrFail($this->resolveRouteParamId($post));

        return view('admin.blog.posts.show', ['post' => $postModel]);
    }

    public function edit($post)
    {
        $postModel = \App\Models\BlogPost::findOrFail($this->resolveRouteParamId($post));

        return view('admin.blog.posts.edit', ['post' => $postModel]);
    }

    public function update(Request $request, $post)
    {
        $postModel = \App\Models\BlogPost::findOrFail($this->resolveRouteParamId($post));
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'category_id' => 'nullable|integer',
        ]);

        $this->updateBlogPostAction->handle($postModel, [
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['title']),
            'content' => $validated['content'] ?? $postModel->content,
            'category_id' => $validated['category_id'] ?? $postModel->category_id,
        ]);

        return redirect()->route('admin.blog.posts.edit', ['post' => $postModel->id]);
    }

    public function destroy($post)
    {
        $postModel = \App\Models\BlogPost::findOrFail($this->resolveRouteParamId($post));
        $this->deleteBlogPostAction->handle($postModel);

        return redirect()->route('admin.blog.posts.index');
    }

    public function publish($post)
    {
        $postModel = \App\Models\BlogPost::findOrFail($this->resolveRouteParamId($post));
        $this->setBlogPostPublishStateAction->handle($postModel, true);

        return response()->json(['success' => true]);
    }

    public function unpublish($post)
    {
        $postModel = \App\Models\BlogPost::findOrFail($this->resolveRouteParamId($post));
        $this->setBlogPostPublishStateAction->handle($postModel, false);

        return response()->json(['success' => true]);
    }

    public function feature($post)
    {
        $postModel = \App\Models\BlogPost::findOrFail($this->resolveRouteParamId($post));
        $this->setBlogPostFeaturedStateAction->handle($postModel, true);

        return response()->json(['success' => true]);
    }

    public function stick($post)
    {
        $postModel = \App\Models\BlogPost::findOrFail($this->resolveRouteParamId($post));
        $this->setBlogPostStickyStateAction->handle($postModel, true);

        return response()->json(['success' => true]);
    }

    private function resolveRouteParamId($value)
    {
        if (is_object($value) && isset($value->id)) {
            return $value->id;
        }

        return $value;
    }
}
