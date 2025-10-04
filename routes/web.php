<?php

use App\Http\Controllers\Backend\ApplicationReviewController;
use App\Http\Controllers\Backend\AssessmentController;
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
use App\Http\Controllers\Backend\SelfAssessmentController;
use App\Http\Controllers\Backend\ServiceUnitController;
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
        Route::resource('profile', ProfileController::class)
            ->parameters(['profile' => 'user'])
            ->names('profile');

        // switch หน่วยบริการที่ topbar
        Route::post('service-unit/switch', [ServiceUnitController::class, 'switch'])->name('service-unit.switch');

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

        // สิทธิ์การใช้งาน
        Route::resource('role', RoleController::class);
        Route::post('role/reorder', [RoleController::class, 'reorder'])->name('role.reorder');

        // tinymce uploads
        Route::post('upload/tinymce', [UploadController::class, 'tinymce'])->name('upload.tinymce');

        // จำลอง login
        Route::post('impersonate/{user}', [ImpersonateController::class, 'start'])->name('impersonate.start');
        Route::get('impersonate/stop', [ImpersonateController::class, 'stop'])->name('impersonate.stop');

        // ประเมินตนเอง (Step 1)
        Route::get('assessment', [AssessmentController::class, 'index'])->name('assessment.index');
        Route::get('assessment/step1/create', [AssessmentController::class, 'create_step1'])->name('assessment.step1.create');
        Route::post('assessment/step1', [AssessmentController::class, 'store_step1'])->name('assessment.step1.store');
        Route::get('assessment/{id}/edit', [AssessmentController::class, 'edit'])->name('assessment.edit');
        Route::put('assessment/{id}', [AssessmentController::class, 'update'])->name('assessment.update');
        Route::delete('assessment/{id}', [AssessmentController::class, 'destroy'])->name('assessment.destroy');

        // ===== SelfAssessmentController (ย้ายเข้า backend) =====
        Route::prefix('self-assess')->name('self.')->group(function () {
            Route::get('/', [SelfAssessmentController::class, 'index'])->name('index');
            Route::get('/create', [SelfAssessmentController::class, 'create'])->name('create');
            Route::post('/', [SelfAssessmentController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [SelfAssessmentController::class, 'edit'])->name('edit');
            Route::put('/{id}', [SelfAssessmentController::class, 'update'])->name('update');
            Route::post('/{id}/submit', [SelfAssessmentController::class, 'submit'])->name('submit');
            Route::get('/{id}', [SelfAssessmentController::class, 'show'])->name('show');
            Route::delete('/{id}', [SelfAssessmentController::class, 'destroy'])->name('destroy');

            // ทบทวนโดย สคร./ส่วนกลาง
            Route::middleware(['can:self.review'])->group(function () {
                Route::get('/{id}/review', [SelfAssessmentController::class, 'reviewForm'])->name('reviewForm');
                Route::post('/{id}/review', [SelfAssessmentController::class, 'review'])->name('review');
                Route::post('/{id}/approve', [SelfAssessmentController::class, 'approve'])->name('approve');
                Route::post('/{id}/reject', [SelfAssessmentController::class, 'reject'])->name('reject');
            });
        });
        // ===== end SelfAssessment =====
    });

    // ==============================
    // Dynamic Routes (ใช้เฉพาะตอน dev)
    // ==============================
    if (app()->environment('local')) {
        Route::get('{routeName}/{name?}', [HomeController::class, 'pageView']);
    }
});
