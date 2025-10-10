@extends('layouts.main')

@section('title', 'ประวัติการใช้งาน')
@section('breadcrumb-item', 'ระบบ')
@section('breadcrumb-item-active', 'ประวัติการใช้งาน')

@push('css')
    <style>
        .diff-badge {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        }

        .prop-list {
            margin: 0;
            padding-left: 1rem;
        }

        .prop-list li {
            margin: .15rem 0;
        }

        .td-wrap {
            white-space: normal;
            word-break: break-word;
        }

        .log-pre {
            white-space: pre-wrap;
        }
    </style>
@endpush

@section('content')
    @php
        use Illuminate\Support\Str;
    @endphp

    <div class="card">
        {{-- <div class="card-header border-0 pb-0">
            <h5 class="mb-0">บันทึกกิจกรรม (Activity Log)</h5>
        </div> --}}

        <div class="card-body">

            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('backend.logs.index') }}" class="d-flex flex-wrap align-items-center gap-2 mb-3">

                {{-- คำค้น --}}
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text">คำค้น</span>
                    <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="คำอธิบาย / หมวด / ชื่อโมเดล">
                </div>

                {{-- ผู้ใช้ --}}
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text">ผู้ใช้</span>
                    <select name="user_id" class="form-select">
                        <option value="">— ทั้งหมด —</option>
                        @foreach ($users as $id => $name)
                            <option value="{{ $id }}" @selected((string) $id === (string) $userId)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- หมวด (log_name) --}}
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text">หมวด</span>
                    <select name="log_name" class="form-select">
                        <option value="">— ทั้งหมด —</option>
                        @foreach ($logNames as $ln)
                            <option value="{{ $ln }}" @selected($ln === $logName)>{{ $ln }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- บังคับขึ้นบรรทัดใหม่ --}}
                <div class="w-100"></div>

                {{-- เหตุการณ์ (แถวล่าง) --}}
                <div class="input-group mt-2" style="max-width: 260px;">
                    <span class="input-group-text">เหตุการณ์</span>
                    <select name="event" class="form-select">
                        <option value="">— ทั้งหมด —</option>
                        @foreach ($events as $k => $v)
                            <option value="{{ $k }}" @selected($k === $event)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ช่วงวันที่ (แถวล่าง) --}}
                <div class="input-group mt-2" style="max-width: 320px;">
                    <span class="input-group-text">ช่วงวันที่</span>
                    <input id="logs-daterange" type="text" class="form-control" placeholder="ช่วงวันที่ (YYYY-MM-DD - YYYY-MM-DD)" value="{{ $dateFrom && $dateTo ? $dateFrom . ' - ' . $dateTo : '' }}" autocomplete="off">
                </div>

                {{-- hidden values สำหรับ Controller --}}
                <input type="hidden" name="date_from" id="input-date-from" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" id="input-date-to" value="{{ $dateTo }}">

                <button class="btn btn-outline-primary mt-2" type="submit">
                    <i class="ti ti-search me-1"></i> ค้นหา
                </button>

                {{-- ปุ่มล้างค่า (เลือกใช้) --}}
                {{-- @if (request()->query())
                    <a href="{{ route('backend.logs.index') }}" class="btn btn-light border mt-2"> ล้างค่า </a>
                @endif --}}

                {{-- ถ้าอยากให้ขึ้นบรรทัดใหม่เฉพาะจอใหญ่ ใช้ตัวคั่นแบบนี้แทน: --}}
                {{-- <div class="w-100 d-none d-lg-block"></div> --}}
            </form>



            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 160px;">เวลา</th>
                            <th style="width: 180px;">ผู้ใช้</th>
                            <th>รายละเอียด</th>
                            <th style="width: 140px;">หมวด</th>
                            <th style="width: 110px;">เหตุการณ์</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $row)
                            @php
                                $props = $row->properties ?? collect();
                                $new = data_get($props, 'attributes', []);
                                $old = data_get($props, 'old', []);
                                $ip = data_get($props, 'ip');
                                $url = data_get($props, 'url');
                                $ua = data_get($props, 'ua');

                                // diff เฉพาะ key ที่เปลี่ยนจริง
                                $changes = [];
                                if (is_array($new)) {
                                    foreach ($new as $k => $v) {
                                        $ov = $old[$k] ?? null;
                                        if ($ov !== $v) {
                                            $changes[] = [$k, $ov, $v];
                                        }
                                    }
                                }

                                $eventBadge = match ($row->event) {
                                    'created' => 'success',
                                    'updated' => 'warning',
                                    'deleted' => 'danger',
                                    default => 'secondary',
                                };

                                // ตัวช่วยเรนเดอร์ค่าแบบสั้น
                                $renderVal = function ($val) {
                                    if (is_null($val)) {
                                        return 'null';
                                    }
                                    if (is_scalar($val)) {
                                        return Str::limit((string) $val, 120);
                                    }
                                    return '[..]';
                                };
                            @endphp

                            <tr>
                                <td class="small">
                                    <div>{{ $row->created_at->format('Y-m-d H:i:s') }}</div>
                                    @if ($ip)
                                        <div class="text-muted">IP: {{ $ip }}</div>
                                    @endif
                                </td>

                                <td class="small">
                                    <div class="fw-semibold">{{ $row->causer?->name ?? '-' }}</div>
                                    <div class="text-muted">{{ $row->causer?->email }}</div>
                                </td>

                                <td class="td-wrap">
                                    {{-- Description + Model --}}
                                    <div class="mb-1">
                                        <span class="me-1">{{ $row->description ?: '—' }}</span>
                                        @if ($row->subject_type)
                                            <span class="badge bg-light text-dark border">
                                                {{ class_basename($row->subject_type) }}#{{ $row->subject_id }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- URL (ถ้ามี) --}}
                                    @if ($url)
                                        <div class="text-muted small mb-1">
                                            <i class="ti ti-link me-1"></i>{{ $url }}
                                        </div>
                                    @endif

                                    {{-- Diff เฉพาะที่เปลี่ยนจริง --}}
                                    @if (count($changes))
                                        <ul class="prop-list small">
                                            @foreach ($changes as [$k, $ov, $nv])
                                                @php $isWH = ($k === 'org_working_hours_json'); @endphp

                                                <li class="mb-1">
                                                    <span class="badge bg-secondary-subtle text-dark diff-badge">{{ $k }}</span>
                                                    <span class="text-muted">:</span>

                                                    {{-- เคสพิเศษ: แปลงวัน-เวลาทำการ --}}
                                                    @if ($isWH)
                                                        <div class="mt-1">
                                                            @if (!is_null($ov))
                                                                <small class="text-muted d-block">เดิม</small>
                                                                <pre class="bg-light border rounded p-2 mb-1 log-pre">{!! e(workingHoursToThaiLines($ov)) !!}</pre>
                                                            @endif

                                                            <small class="text-muted d-block">ใหม่</small>
                                                            <pre class="bg-light border rounded p-2 mb-0 log-pre">{!! e(workingHoursToThaiLines($nv)) !!}</pre>
                                                        </div>
                                                    @else
                                                        {{-- ค่าอื่น ๆ ใช้รูปแบบเดิม --}}
                                                        <span class="text-decoration-line-through text-muted">
                                                            {{ $renderVal($ov) }}
                                                        </span>
                                                        <i class="ti ti-arrow-right mx-1"></i>
                                                        <span>{{ $renderVal($nv) }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="text-muted small">— ไม่มีการเปลี่ยนแปลงของข้อมูล —</div>
                                    @endif

                                    {{-- UA (ถ้ามี) --}}
                                    @if ($ua)
                                        <div class="text-muted small mt-1"><i class="ti ti-device-desktop me-1"></i>{{ Str::limit($ua, 160) }}</div>
                                    @endif
                                </td>

                                <td>
                                    <span class="badge bg-light text-dark border">{{ $row->log_name }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $eventBadge }}">{{ $row->event ?? '—' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">ไม่พบข้อมูล</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rangeEl = document.getElementById('logs-daterange');
            const fromEl = document.getElementById('input-date-from');
            const toEl = document.getElementById('input-date-to');
            const btnClr = document.getElementById('btn-clear-daterange');

            const startVal = fromEl.value || null; // 'YYYY-MM-DD' หรือ null
            const endVal = toEl.value || null;

            const fp = flatpickr(rangeEl, {
                mode: 'range',
                dateFormat: 'Y-m-d', // ค่าที่ส่งไปหลังบ้าน (คงรูปแบบเดิม)
                locale: 'th', // 👈 ใช้ภาษาไทย (วัน/เดือน/ปุ่มต่าง ๆ)
                defaultDate: [startVal, endVal].filter(Boolean),
                allowInput: true,
                conjunction: ' - ', // แสดงเป็น "2025-10-01 - 2025-10-10"
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        const s = instance.formatDate(selectedDates[0], 'Y-m-d');
                        const e = instance.formatDate(selectedDates[1], 'Y-m-d');
                        fromEl.value = s;
                        toEl.value = e;
                    } else if (selectedDates.length === 1) {
                        fromEl.value = instance.formatDate(selectedDates[0], 'Y-m-d');
                        toEl.value = '';
                    } else {
                        fromEl.value = '';
                        toEl.value = '';
                    }
                },
                onReady: function(_, __, instance) {
                    // sync ค่าแสดงผลตอนเปิดหน้า (ถ้ามีค่าเริ่มต้น)
                    if (startVal && endVal) {
                        rangeEl.value = startVal + ' - ' + endVal;
                    }
                }
            });

            // ปุ่มล้างช่วง
            btnClr?.addEventListener('click', function() {
                fp.clear();
                rangeEl.value = '';
                fromEl.value = '';
                toEl.value = '';
            });
        });
    </script>
@endpush
