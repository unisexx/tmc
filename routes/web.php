<?php

use App\Http\Controllers\Backend\ApplicationReviewController;
use App\Http\Controllers\Backend\AssessmentController;
use App\Http\Controllers\Backend\AssessmentFillController;
use App\Http\Controllers\Backend\ContactController;
use App\Http\Controllers\Backend\CookiePolicyController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\FaqController;
use App\Http\Controllers\Backend\HilightController;
use App\Http\Controllers\Backend\ImpersonateController;
use App\Http\Controllers\Backend\NewsController;
use App\Http\Controllers\Backend\PrivacyPolicyController;
use App\Http\Controllers\Backend\ProfileController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\StatController;
use App\Http\Controllers\Backend\UploadController;
use App\Http\Controllers\Backend\UserController;
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
        // แก้ไขข้อมูลส่วนตัว
        Route::resource('profile', \App\Http\Controllers\Backend\ProfileController::class)
            ->parameters(['profile' => 'user']) // เปลี่ยน {profile} -> {user}
            ->names('profile');

        // static/simple pages
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/stat', [StatController::class, 'index'])->name('stat');

        // ไฮไลท์
        Route::resource('hilight', HilightController::class)->names('hilight');
        Route::post('hilight/reorder', [HilightController::class, 'reorder'])->name('hilight.reorder');

        // ข่าวประชาสัมพันธ์
        Route::resource('news', NewsController::class)->names('news');

        // คำถามที่พบบ่อย
        Route::resource('faq', FaqController::class)->names('faq');
        Route::post('faq/reorder', [FaqController::class, 'reorder'])->name('faq.reorder');
        Route::post('faq/{faq}/view', [FaqController::class, 'countView'])->name('faq.countView');

        // ติดต่อเรา
        Route::get('contact', [ContactController::class, 'edit'])->name('contact.edit');
        Route::put('contact', [ContactController::class, 'update'])->name('contact.update');

        // นโยบายส่วนบุคคล
        Route::get('privacy-policy', [PrivacyPolicyController::class, 'edit'])->name('privacy.edit');
        Route::put('privacy-policy', [PrivacyPolicyController::class, 'update'])->name('privacy.update');

        // นโยบายคุกกี้
        Route::get('cookie-policy', [CookiePolicyController::class, 'edit'])->name('cookie.edit');
        Route::put('cookie-policy', [CookiePolicyController::class, 'update'])->name('cookie.update');

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

        // ตรวจสอบใบสมัคร
        Route::resource('application-review', ApplicationReviewController::class)
            ->parameters(['application-review' => 'user'])
            ->names('application-review');

        // จัดการผู้ใช้งาน
        Route::resource('user', UserController::class)->names('user');
        Route::prefix('user')->name('user.')->group(function () {
            Route::get('{user}/detail', [UserController::class, 'detail'])->name('detail');
            Route::get('{user}/download-pdf', [UserController::class, 'downloadPdf'])->name('downloadPdf');
            Route::post('{user}/reset-password', [UserController::class, 'resetPassword'])->name('resetPassword');
            Route::get('export', [UserController::class, 'export'])->name('export');
        });

        // สิทธิ์การใช้งาน (Roles & Permissions)
        Route::resource('role', RoleController::class);

        // tinymce uploads
        Route::post('upload/tinymce', [UploadController::class, 'tinymce'])->name('upload.tinymce');

        // จำลอง login
        Route::post('/impersonate/{user}', [ImpersonateController::class, 'start'])->name('impersonate.start');
        Route::get('/impersonate/stop', [ImpersonateController::class, 'stop'])->name('impersonate.stop');

        // ประเมินตนเอง
        Route::get('assessment', [AssessmentController::class, 'index'])->name('assessment.index');

        // Step 1
        Route::get('assessment/step1/create', [AssessmentController::class, 'create_step1'])
            ->name('assessment.step1.create');
        Route::post('assessment/step1', [AssessmentController::class, 'store_step1'])
            ->name('assessment.step1.store');

        // CRUD หลัก
        Route::get('assessment/{id}/edit', [AssessmentController::class, 'edit'])->name('assessment.edit');
        Route::put('assessment/{id}', [AssessmentController::class, 'update'])->name('assessment.update'); // << เพิ่มบรรทัดนี้
        Route::delete('assessment/{id}', [AssessmentController::class, 'destroy'])->name('assessment.destroy');

    });

    // ==============================
    // Dynamic Routes (ใช้เฉพาะตอน dev)
    // ==============================
    if (app()->environment('local')) {
        Route::get('{routeName}/{name?}', [HomeController::class, 'pageView']);
    }
});
