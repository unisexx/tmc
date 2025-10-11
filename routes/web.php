<?php

use App\Http\Controllers\Backend\ActivityLogController;
use App\Http\Controllers\Backend\ApplicationReviewController;
use App\Http\Controllers\Backend\AssessmentReviewController;
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
use App\Http\Controllers\Backend\SelfAssessmentComponentController;
use App\Http\Controllers\Backend\SelfAssessmentServiceUnitLevelController;
use App\Http\Controllers\Backend\ServiceUnitController;
use App\Http\Controllers\Backend\ServiceUnitProfileController;
use App\Http\Controllers\Backend\StatController;
use App\Http\Controllers\Backend\UploadController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\GeoApiController;
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

        // จัดการหน่วยบริการ
        Route::resource('service-unit', ServiceUnitController::class)->names('service-unit');

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

        // แก้ไขหน่วยบริการที่ตัวเองรับผิดชอบ
        Route::get('/service-unit-profile', [ServiceUnitProfileController::class, 'edit'])
            ->name('service-unit-profile.edit');
        Route::put('/service-unit-profile', [ServiceUnitProfileController::class, 'update'])
            ->name('service-unit-profile.update');

        // tinymce uploads
        Route::post('upload/tinymce', [UploadController::class, 'tinymce'])->name('upload.tinymce');

        // จำลอง login
        Route::post('impersonate/{user}', [ImpersonateController::class, 'start'])->name('impersonate.start');
        Route::get('impersonate/stop', [ImpersonateController::class, 'stop'])->name('impersonate.stop');

        // ประเมินตนเอง (ประเมินระดับของหน่วยบริการ)
        Route::resource('self-assessment-service-unit-level', SelfAssessmentServiceUnitLevelController::class)
            ->names('self-assessment-service-unit-level') // ให้ชื่อเป็น backend.assessment-service-unit-level.*
            ->parameters(['self-assessment-service-unit-level' => 'id']);

        // ประเมินตนเอง (6 องค์ประกอบ)
        Route::get('/self-assessments/{suLevelId}/create', [SelfAssessmentComponentController::class, 'create'])->name('self-assessment-component.create');
        Route::post('/self-assessments/{suLevelId}/save', [SelfAssessmentComponentController::class, 'save'])->name('self-assessment-component.save'); // เซฟทับเสมอ

        // export ผลการประเมิน
        Route::get('self-assessment-service-unit-level/{id}/export-pdf', [SelfAssessmentServiceUnitLevelController::class, 'exportPdf'])->name('self-assessment-service-unit-level.export-pdf');

        // ดาวน์โหลดไฟล์แนบ ที่แนบมากับแบบประเมิน
        Route::get('attachments/{id}', [SelfAssessmentServiceUnitLevelController::class, 'downloadAttachment'])->name('attachments.download');

        /* ตรวจสอบผลการประเมิน */
        Route::get('review-assessment', [AssessmentReviewController::class, 'index'])->name('review-assessment.index');
        Route::get('review-assessment/{id}', [AssessmentReviewController::class, 'show'])->whereNumber('id')->name('review-assessment.show');
        Route::put('review-assessment/{id}/status', [AssessmentReviewController::class, 'updateStatus'])->whereNumber('id')->name('review-assessment.status');
        Route::delete('review-assessment/{id}', [AssessmentReviewController::class, 'destroy'])->whereNumber('id')->name('review-assessment.destroy');

        // ประวัติการใช้งาน
        Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
    });
});

Route::prefix('geo')->group(function () {
    Route::get('/provinces', [GeoApiController::class, 'provinces'])->name('geo.provinces');
    Route::get('/districts', [GeoApiController::class, 'districts'])->name('geo.districts');
    Route::get('/subdistricts', [GeoApiController::class, 'subdistricts'])->name('geo.subdistricts');
    Route::get('/postcodes', [GeoApiController::class, 'postcodes'])->name('geo.postcodes');
    Route::get('/subdistrict-center', [GeoApiController::class, 'subdistrictCenter'])->name('geo.subdistrict-center');
});

// ==============================
// Dynamic Routes (ใช้เฉพาะตอน dev) ของ Light Abel Template (อย่าลืมลบออก)
// ==============================
Route::middleware(['auth'])->group(function () {
    // ... ทั้งหมดของ backend
    if (app()->environment('local')) {
        Route::get('{routeName}/{name?}', [HomeController::class, 'pageView']);
    }
});
