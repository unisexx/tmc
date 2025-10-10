<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $req)
    {
        // รับค่า filter
        $q        = trim($req->get('q', ''));
        $userId   = $req->get('user_id');
        $logName  = $req->get('log_name');
        $event    = $req->get('event');     // created / updated / deleted / null
        $dateFrom = $req->get('date_from'); // Y-m-d
        $dateTo   = $req->get('date_to');   // Y-m-d

        $builder = Activity::query()
            ->with(['causer' => function ($q) {$q->select('id', 'name', 'email');}])
            ->orderByDesc('id');

        if ($q !== '') {
            $builder->where(function ($sub) use ($q) {
                $sub->where('description', 'like', "%{$q}%")
                    ->orWhere('subject_type', 'like', "%{$q}%")
                    ->orWhere('log_name', 'like', "%{$q}%");
            });
        }

        if ($userId) {
            $builder->where('causer_id', $userId);
        }

        if ($logName) {
            $builder->where('log_name', $logName);
        }

        if ($event) {
            $builder->where('event', $event);
        }

        if ($dateFrom) {
            $builder->whereDate('created_at', '>=', Carbon::parse($dateFrom)->format('Y-m-d'));
        }
        if ($dateTo) {
            $builder->whereDate('created_at', '<=', Carbon::parse($dateTo)->format('Y-m-d'));
        }

        $logs = $builder->paginate(10)->appends($req->all());

        // options สำหรับฟอร์ม
        $users    = User::orderBy('name')->pluck('name', 'id');
        $logNames = Activity::query()->select('log_name')->distinct()->orderBy('log_name')->pluck('log_name');
        $events   = ['created' => 'สร้าง', 'updated' => 'แก้ไข', 'deleted' => 'ลบ'];

        return view('backend.logs.index', compact('logs', 'users', 'logNames', 'events', 'q', 'userId', 'logName', 'event', 'dateFrom', 'dateTo'));
    }
}
