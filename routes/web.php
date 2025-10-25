<?php

use App\Http\Controllers\AjaxController;
use App\Http\Controllers\Backend\ActivityLogController;
use App\Http\Controllers\Backend\ApplicationReviewController;
use App\Http\Controllers\Backend\AssessmentFormServiceSettingController;
use App\Http\Controllers\Backend\AssessmentReviewController;
use App\Http\Controllers\Backend\AssessmentServiceConfigController;
use App\Http\Controllers\Backend\ContactController;
use App\Http\Controllers\Backend\ContactMessageController;
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
use App\Http\Controllers\Backend\StHealthServiceController;
use App\Http\Controllers\Backend\UploadController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Frontend\ContactController as FrontContactController;
use App\Http\Controllers\Frontend\FaqController as FrontFaqController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\NewsController as FrontNewsController;
use App\Http\Controllers\Frontend\ServiceUnitController as FrontServiceUnit;
use App\Http\Controllers\Frontend\ServiceUnitMessageController as FrontServiceUnitMessage;
use App\Http\Controllers\GeoApiController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes();

// ==============================
// Frontend Routes
// ==============================
Route::get('/', [HomeController::class, 'home'])->name('home');
Route::prefix('news')->name('frontend.news.')->group(function () {
    Route::get('/', [FrontNewsController::class, 'index'])->name('index');
    Route::get('/{slug}', [FrontNewsController::class, 'show'])->name('show');
});
Route::prefix('faq')->name('frontend.faq.')->group(function () {
    Route::get('/', [FrontFaqController::class, 'index'])->name('index');
});
Route::prefix('contact')->name('frontend.contact.')->group(function () {
    Route::get('/', [FrontContactController::class, 'index'])->name('index');
    Route::post('/send', [FrontContactController::class, 'send'])
        ->middleware('throttle:3,2')
        ->name('send');
});
Route::prefix('service-units')->name('frontend.service-units.')->group(function () {
    Route::get('/', [FrontServiceUnit::class, 'index'])->name('index');
});
Route::post('/units/{serviceUnit}/messages', [FrontServiceUnitMessage::class, 'store'])
    ->middleware('throttle:3,2')
    ->name('frontend.units.messages.store');

// ==============================
// Backend Routes
// ==============================
Route::middleware(['auth'])->group(function () {

    // Root URL
    // Route::get('/', fn() => view('index'))->name('home');

    Route::prefix('backend')->name('backend.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/unit', [DashboardController::class, 'unit'])->name('dashboard.unit');
        Route::get('/dashboard/gap-units', [DashboardController::class, 'gapUnits'])->name('dashboard.gap-units');
        Route::get('/dashboard/export-overview', [DashboardController::class, 'exportOverview'])->name('dashboard.export-overview');
        Route::get('/dashboard/export-unit', [DashboardController::class, 'exportUnit'])->name('dashboard.export-unit');

        // สถิติการเข้าเว็บ
        Route::get('/stat', [StatController::class, 'index'])->name('stat');

        // แก้ไขข้อมูลส่วนตัว
        Route::resource('profile', ProfileController::class)
            ->parameters(['profile' => 'user'])
            ->names('profile');

        // จัดการหน่วยบริการ
        Route::resource('service-unit', ServiceUnitController::class)->names('service-unit');

        // ผู้รับผิดชอบหน่วยงาน
        Route::get('service-unit/{service_unit}/managers', [ServiceUnitController::class, 'managers'])
            ->name('service-unit.managers.edit');
        Route::put('service-unit/{service_unit}/managers', [ServiceUnitController::class, 'managersUpdate'])
            ->name('service-unit.managers.update');

        // switch หน่วยบริการที่ topbar
        Route::post('service-unit/switch', [ServiceUnitController::class, 'switch'])->name('service-unit.switch');

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
        Route::get('/application/revise/{user}', [ApplicationReviewController::class, 'reviseForm'])
            ->name('application.revise')->middleware('signed');
        Route::put('/application/revise/{user}', [ApplicationReviewController::class, 'reviseSubmit'])
            ->name('application.revise.submit')->middleware('signed');

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
        Route::post('/self-assessments/{suLevelId}/autosave', [SelfAssessmentComponentController::class, 'save'])
            ->name('self-assessment-component.autosave'); // 🌟 AUTOSAVE (XHR) 🌟

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

        // ตั้งค่า การให้บริการของหน่วยบริการ
        Route::resource('st-health-services', StHealthServiceController::class)->except(['show']);
        Route::post('/st-health-services/reorder', [StHealthServiceController::class, 'reorder'])
            ->name('st-health-services.reorder');

        // ตั้งค่าสวิตช์ต่อฟอร์มประเมินรายหน่วยรายรอบ
        // Route::get('/assessment-forms/{form}/services', [AssessmentFormServiceSettingController::class, 'edit'])
        //     ->name('assessment-forms.services.edit');
        // Route::put('/assessment-forms/{form}/services', [AssessmentFormServiceSettingController::class, 'update'])
        //     ->name('assessment-forms.services.update');
        // Route::patch('/assessment-forms/{form}/services/toggle', [AssessmentFormServiceSettingController::class, 'toggle'])
        //     ->name('assessment-forms.services.toggle');

        // ตั้งค่าการให้บริการราย "ระดับหน่วยบริการ" ที่ผ่านการอนุมัติแล้ว approval_status = 'approved'
        Route::get('/assessment-service-unit-level/{level}/services', [AssessmentServiceConfigController::class, 'edit'])
            ->name('assessment-service-configs.services.edit');
        Route::put('/assessment-service-unit-level/{level}/services', [AssessmentServiceConfigController::class, 'update'])
            ->name('assessment-service-configs.services.update');
        Route::patch('assessment-service-unit-level/{level}/services/toggle', [AssessmentServiceConfigController::class, 'toggle'])
            ->name('assessment-service-configs.services.toggle');

        // กล่องข้อความ
        Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::get('/contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
        Route::patch('/contact-messages/{contactMessage}/done', [ContactMessageController::class, 'markDone'])->name('contact-messages.markDone');
        Route::post('/contact-messages/{contactMessage}/reply', [ContactMessageController::class, 'reply'])->middleware('throttle:5,1')->name('contact-messages.reply');
    });
});

// Chain Select จังหวัด อำเภอ ตำบล รหัสไปรษณีย์ lat lon
Route::prefix('geo')->group(function () {
    Route::get('/provinces', [GeoApiController::class, 'provinces'])->name('geo.provinces');
    Route::get('/districts', [GeoApiController::class, 'districts'])->name('geo.districts');
    Route::get('/subdistricts', [GeoApiController::class, 'subdistricts'])->name('geo.subdistricts');
    Route::get('/postcodes', [GeoApiController::class, 'postcodes'])->name('geo.postcodes');
    Route::get('/subdistrict-center', [GeoApiController::class, 'subdistrictCenter'])->name('geo.subdistrict-center');
});

// Ajax
// lookup ผู้ใช้
Route::get('/users/lookup', [AjaxController::class, 'ajaxUserLookup'])->name('ajax.users.lookup');

// chain select: สคร. → จังหวัด → หน่วยบริการ
Route::get('/regions/{region}/provinces', [AjaxController::class, 'ajaxProvincesByRegion'])->name('ajax.cascade.provinces');
Route::get('/provinces/{province}/service-units', [AjaxController::class, 'ajaxServiceUnitsByProvince'])->name('ajax.cascade.serviceUnits');

// ==============================
// Dynamic Routes (ใช้เฉพาะตอน dev) ของ Light Abel Template (อย่าลืมลบออก)
// ==============================
// Route::middleware(['auth'])->group(function () {
//     if (app()->environment('local')) {
//         Route::get('{routeName}/{name?}', [HomeController::class, 'pageView']);
//     }
// });
