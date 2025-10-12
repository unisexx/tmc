<?php

namespace App\Providers;

use App\Models\AssessmentServiceUnitLevel;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Schema::defaultStringLength(191);

        // Paginator::useBootstrapFive();
        Paginator::defaultView('vendor.pagination.lightable');

        // ตัวนับจำนวนผู้สมัครสมาชิก
        View::composer('layouts.sidebar', function ($view) {
            $pendingCount = User::where('reg_status', '!=', 'อนุมัติ')->count();
            $view->with('pendingApplicationCount', $pendingCount);
        });

        // ตัวนับแบบประเมินที่ต้องตรวจสอบของ สคร./สสจ.
        View::composer('layouts.sidebar', function ($view) {
            $user  = Auth::user();
            $count = 0;

            if ($user && $user->can('review-assessment.view')) {
                $query = AssessmentServiceUnitLevel::query()->whereNotNull('submitted_at');

                // ===== SCOPE ตามสิทธิ์ =====
                if (!$user->isAdmin()) {
                    $allowedProvCodes = collect();

                    if ($user->hasPurpose('P') && !empty($user->reg_supervise_province_code)) {
                        $allowedProvCodes->push($user->reg_supervise_province_code);
                    }

                    if ($user->hasPurpose('R') && !empty($user->reg_supervise_region_id)) {
                        $regionProvCodes = DB::table('province')
                            ->where('health_region_id', $user->reg_supervise_region_id)
                            ->pluck('code');
                        $allowedProvCodes = $allowedProvCodes->merge($regionProvCodes);
                    }

                    $allowedProvCodes = $allowedProvCodes->unique()->values();

                    if ($allowedProvCodes->isNotEmpty()) {
                        $query->whereHas('serviceUnit', fn($s) =>
                            $s->whereIn('org_province_code', $allowedProvCodes)
                        );
                    } else {
                        $query->whereRaw('1=0');
                    }
                }

                // นับเฉพาะที่ยังไม่ได้ตรวจสอบ
                $count = $query->where(function ($q) {
                    $q->whereNull('approval_status')
                        ->orWhereIn('approval_status', ['reviewing', 'pending']);
                })->count();

            }

            $view->with('pendingReviewCount', $count);
        });
    }
}
