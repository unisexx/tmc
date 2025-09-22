<?php

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\FaqController;
use App\Http\Controllers\Backend\HilightController;
use App\Http\Controllers\Backend\NewsController;
use App\Http\Controllers\Backend\StatController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes();

Route::middleware(['auth'])->group(function () {

    // Root URL
    Route::get('/', fn() => view('index'))->name('home');

    // ==============================
    // Backend Routes (ตายตัว - production)
    // ==============================
    Route::prefix('backend')->name('backend.')->group(function () {

        // static/simple pages
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/stat', [StatController::class, 'index'])->name('stat');

        Route::resource('hilight', HilightController::class)->names('hilight');
        Route::post('hilight/reorder', [HilightController::class, 'reorder'])->name('hilight.reorder');

        Route::resource('news', NewsController::class)->names('news');

        Route::resource('faq', FaqController::class)->names('faq');
        Route::post('faq/reorder', [FaqController::class, 'reorder'])->name('faq.reorder');
        Route::post('faq/{faq}/view', [FaqController::class, 'countView'])->name('faq.countView');

        // // pages ที่มีพารามิเตอร์ (ตัวอย่างหน้าแก้ไขเนื้อหา)
        // Route::get('/contact/{id}/edit', fn($id) =>
        //     view('backend.contact.edit', compact('id'))
        // )->name('contact.edit');

        // Route::get('/privacy-policy/{id}/edit', fn($id) =>
        //     view('backend.policy.privacy_edit', compact('id'))
        // )->name('privacy.edit');

        // Route::get('/cookie-policy/{id}/edit', fn($id) =>
        //     view('backend.policy.cookie_edit', compact('id'))
        // )->name('cookie.edit');

        // // รายการต่าง ๆ
        // Route::get('/users', fn() => view('backend.users.index'))->name('users.index');
        // Route::get('/permissions', fn() => view('backend.permissions.index'))->name('permissions.index');
        // Route::get('/logs', fn() => view('backend.logs.index'))->name('logs.index');
    });

    // ==============================
    // Dynamic Routes (ใช้เฉพาะตอน dev)
    // ==============================
    if (app()->environment('local')) {
        Route::get('{routeName}/{name?}', [HomeController::class, 'pageView']);
    }
});
