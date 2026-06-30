<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Blog Routes
|--------------------------------------------------------------------------
*/

// Blog Yönetimi (Ayrı dosyada)
Route::prefix('/blog')->name('blog.')->group(function () {
    // Blog Dashboard
    Route::get('/', [\App\Http\Controllers\Admin\BlogController::class, 'index'])->name('index');

    // Blog Posts
    Route::resource('/posts', \App\Http\Controllers\Admin\BlogController::class)->parameters(['posts' => 'post']);

    // Blog Post Actions
    Route::post('/posts/{post}/publish', [\App\Http\Controllers\Admin\BlogController::class, 'publish'])->name('posts.publish');
    Route::post('/posts/{post}/unpublish', [\App\Http\Controllers\Admin\BlogController::class, 'unpublish'])->name('posts.unpublish');
    Route::post('/posts/{post}/feature', [\App\Http\Controllers\Admin\BlogController::class, 'feature'])->name('posts.feature');
    Route::post('/posts/{post}/stick', [\App\Http\Controllers\Admin\BlogController::class, 'stick'])->name('posts.stick');

    // Blog Categories
    Route::prefix('/categories')->name('categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BlogController::class, 'categories'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\BlogController::class, 'createCategory'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\BlogController::class, 'storeCategory'])->name('store');
        Route::get('/{category}/edit', [\App\Http\Controllers\Admin\BlogController::class, 'editCategory'])->name('edit');
        Route::put('/{category}', [\App\Http\Controllers\Admin\BlogController::class, 'updateCategory'])->name('update');
        Route::delete('/{category}', [\App\Http\Controllers\Admin\BlogController::class, 'destroyCategory'])->name('destroy');
        Route::post('/{category}/toggle', [\App\Http\Controllers\Admin\BlogController::class, 'toggleCategory'])->name('toggle');
    });

    // Blog Tags
    Route::prefix('/tags')->name('tags.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BlogController::class, 'tags'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\BlogController::class, 'createTag'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\BlogController::class, 'storeTag'])->name('store');
        Route::get('/{tag}/edit', [\App\Http\Controllers\Admin\BlogController::class, 'editTag'])->name('edit');
        Route::put('/{tag}', [\App\Http\Controllers\Admin\BlogController::class, 'updateTag'])->name('update');
        Route::delete('/{tag}', [\App\Http\Controllers\Admin\BlogController::class, 'destroyTag'])->name('destroy');
        // Toggle isteği varsa patch ile yakalayalım
        Route::patch('/{tag}/toggle', [\App\Http\Controllers\Admin\BlogController::class, 'toggleTag'])
            ->name('toggle');
    });

    // Blog Comments
    Route::prefix('/comments')->name('comments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BlogController::class, 'comments'])->name('index');
        Route::post('/{comment}/approve', [\App\Http\Controllers\Admin\BlogController::class, 'approveComment'])->name('approve');
        Route::post('/{comment}/reject', [\App\Http\Controllers\Admin\BlogController::class, 'rejectComment'])->name('reject');
        Route::post('/{comment}/spam', [\App\Http\Controllers\Admin\BlogController::class, 'markCommentAsSpam'])->name('spam');
    });

    // Blog Analytics
    Route::get('/analytics', [\App\Http\Controllers\Admin\BlogController::class, 'analytics'])->name('analytics');
    Route::post('/clear-cache', [\App\Http\Controllers\Admin\BlogController::class, 'clearSidebarCache'])->name('clear-cache');
});
