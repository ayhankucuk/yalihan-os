<?php

use App\Http\Controllers\Admin\BlogController;
use Illuminate\Support\Facades\Route;

// Blog routes
Route::prefix('/blog')->name('blog.')->group(function () {
    // Blog Dashboard
    Route::get('/', [BlogController::class, 'index'])->name('index');

    // Blog Posts
    Route::resource('/posts', BlogController::class)->parameters(['posts' => 'post']);

    // Blog Post Actions
    Route::post('/posts/{post}/publish', [BlogController::class, 'publish'])->name('posts.publish');
    Route::post('/posts/{post}/unpublish', [BlogController::class, 'unpublish'])->name('posts.unpublish');
    Route::post('/posts/{post}/feature', [BlogController::class, 'feature'])->name('posts.feature');
    Route::post('/posts/{post}/stick', [BlogController::class, 'stick'])->name('posts.stick');

    // Blog Categories
    Route::prefix('/categories')->name('categories.')->group(function () {
        Route::get('/', [BlogController::class, 'categories'])->name('index');
        Route::get('/create', [BlogController::class, 'createCategory'])->name('create');
        Route::post('/', [BlogController::class, 'storeCategory'])->name('store');
        Route::get('/{category}/edit', [BlogController::class, 'editCategory'])->name('edit');
        Route::put('/{category}', [BlogController::class, 'updateCategory'])->name('update');
        Route::delete('/{category}', [BlogController::class, 'destroyCategory'])->name('destroy');
        Route::post('/{category}/toggle', [BlogController::class, 'toggleCategory'])->name('toggle');
    });

    // Blog Tags
    Route::prefix('/tags')->name('tags.')->group(function () {
        Route::get('/', [BlogController::class, 'tags'])->name('index');
        Route::get('/create', [BlogController::class, 'createTag'])->name('create');
        Route::post('/', [BlogController::class, 'storeTag'])->name('store');
        Route::get('/{tag}/edit', [BlogController::class, 'editTag'])->name('edit');
        Route::put('/{tag}', [BlogController::class, 'updateTag'])->name('update');
        Route::delete('/{tag}', [BlogController::class, 'destroyTag'])->name('destroy');
        Route::patch('/{tag}/toggle', [BlogController::class, 'toggleTag'])->name('toggle');
    });

    // Blog Comments
    Route::prefix('/comments')->name('comments.')->group(function () {
        Route::get('/', [BlogController::class, 'comments'])->name('index');
        Route::post('/{comment}/approve', [BlogController::class, 'approveComment'])->name('approve');
        Route::post('/{comment}/reject', [BlogController::class, 'rejectComment'])->name('reject');
        Route::post('/{comment}/spam', [BlogController::class, 'markCommentAsSpam'])->name('spam');
    });

    // Blog Analytics
    Route::get('/analytics', [BlogController::class, 'analytics'])->name('analytics');
    Route::post('/clear-cache', [BlogController::class, 'clearSidebarCache'])->name('clear-cache');
});
