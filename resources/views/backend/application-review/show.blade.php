{{-- resources/views/backend/application-review/show.blade.php --}}

@extends('layouts.main')

@section('title', 'ตรวจสอบใบสมัคร: ' . ($user->contact_name ?? ($user->name ?? $user->username)))
@section('breadcrumb-item', 'ผู้ใช้งาน')
@section('breadcrumb-item-active', 'ตรวจสอบใบสมัคร')

@php
    // ป้าย purpose
    $purposeBadges = collect($selectedLabels ?? [])
        ->map(function ($label) {
            $map = [
                'หน่วยบริการสุขภาพผู้เดินทาง' => 'primary',
                'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)' => 'warning',
                'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)' => 'success',
            ];
            $cls = $map[$label] ?? 'secondary';
            return "<span class=\"badge bg-{$cls} me-1\">{$label}</span>";
        })
        ->implode(' ');
@endphp

@section('content')

    <x-register.error-summary :errors="$errors" />

    <div class="row g-3">
        {{-- ===== สรุปภาพรวม ===== --}}
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="ph-duotone ph-identification-card fs-4"></i>
                    <h5 class="mb-0">สรุปใบสมัคร</h5>
                    <span class="ms-auto text-muted small">
                        อัปเดตล่าสุด: {{ optional($user->updated_at)->format('d/m/Y H:i') ?? '-' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        {{-- 1) วัตถุประสงค์การลงทะเบียน --}}
                        <div class="col-12">
                            <h6 class="mb-2"><i class="ph-duotone ph-target me-1"></i>1) วัตถุประสงค์การลงทะเบียน</h6>
                            <div>{!! $purposeBadges ?: '<span class="text-muted">-</span>' !!}</div>

                            @if (in_array('ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)', $selectedLabels ?? [], true))
                                <div class="mt-2 small text-muted">
                                    <i class="ph-duotone ph-map-pin me-1"></i>
                                    จังหวัดที่กำกับดูแล (สสจ.): {{ $user->superviseProvince->title ?? '-' }}
                                </div>
                            @endif
                            @if (in_array('ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)', $selectedLabels ?? [], true))
                                <div class="mt-1 small text-muted">
                                    <i class="ph-duotone ph-map-pin-area me-1"></i>
                                    เขตสุขภาพ (สคร.): {{ optional($user->superviseRegion)->short_title ? $user->superviseRegion->short_title . ' - ' : '' }}{{ $user->superviseRegion->title ?? '-' }}
                                </div>
                            @endif
                        </div>

                        <hr class="my-3">

                        {{-- 2) หน่วยบริการ/หน่วยงาน --}}
                        <div class="col-12">
                            <h6 class="mb-2"><i class="ph-duotone ph-hospital me-1"></i>2) ข้อมูลหน่วยบริการ/หน่วยงาน</h6>
                            @if ($unit)
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="mb-2"><strong>ชื่อหน่วย:</strong> {{ $unit->org_name ?? '-' }}</div>
                                            <div class="mb-2">
                                                <strong>สังกัด:</strong> {{ $unit->org_affiliation ?? '-' }}
                                                @if (($unit->org_affiliation ?? '') === 'อื่น ๆ' && !empty($unit->org_affiliation_other))
                                                    ({{ $unit->org_affiliation_other }})
                                                @endif
                                            </div>
                                            <div class="mb-2"><strong>โทรศัพท์:</strong> {{ $unit->org_tel ?? '-' }}</div>
                                            <div class="mb-2"><strong>ที่อยู่:</strong>
                                                <div class="text-wrap">{{ $unit->org_address ?? '-' }}</div>
                                                <div class="small text-muted">
                                                    จ.{{ $unit->province->title ?? '-' }}
                                                    อ.{{ $unit->district->title ?? '-' }}
                                                    ต.{{ $unit->subdistrict->title ?? '-' }}
                                                    {{ $unit->org_postcode ?? '' }}
                                                </div>
                                            </div>
                                            <div class="mb-2"><strong>พิกัด:</strong>
                                                {{ $unit->org_lat && $unit->org_lng ? "{$unit->org_lat}, {$unit->org_lng}" : '-' }}
                                                @if ($unit->org_lat && $unit->org_lng)
                                                    <a class="ms-2 small" target="_blank" href="https://www.google.com/maps?q={{ $unit->org_lat }},{{ $unit->org_lng }}">
                                                        เปิดแผนที่
                                                    </a>
                                                @endif
                                            </div>

                                            {{-- แผนที่ย่อใต้ที่อยู่ --}}
                                            @if (!empty($unit->org_lat) && !empty($unit->org_lng))
                                                <div id="unit-mini-map" class="rounded border" style="height:220px;"></div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <div><strong>วัน-เวลาทำการ:</strong></div>
                                            {{-- ใช้ helper แสดงตารางวัน-เวลาทำการ --}}
                                            <div class="table-responsive">
                                                {!! renderWorkingHoursTable($unit->org_working_hours_json ?? null) !!}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-muted">ไม่มีข้อมูลหน่วยบริการผูกกับผู้ใช้นี้</div>
                            @endif
                        </div>

                        <hr class="my-3">

                        {{-- 3) ผู้ลงทะเบียน --}}
                        <div class="col-12">
                            <h6 class="mb-2"><i class="ph-duotone ph-user-list me-1"></i>3) ข้อมูลผู้ลงทะเบียน</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <tbody>
                                        <tr>
                                            <th class="w-25">เลขบัตรประชาชน</th>
                                            <td>{{ $user->contact_cid ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>ชื่อ-สกุล</th>
                                            <td>{{ $user->contact_name ?? ($user->name ?? '-') }}</td>
                                        </tr>
                                        <tr>
                                            <th>ตำแหน่ง</th>
                                            <td>{{ $user->contact_position ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>โทรศัพท์มือถือ</th>
                                            <td>{{ $user->contact_mobile ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>อีเมล</th>
                                            <td>{{ $user->email ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>ไฟล์เอกสารเจ้าหน้าที่</th>
                                            <td>
                                                @if (!empty($user->officer_doc_path))
                                                    <a href="{{ Storage::disk('public')->url($user->officer_doc_path) }}" target="_blank">
                                                        {{ basename($user->officer_doc_path) }}
                                                    </a>
                                                    @if ($user->officer_doc_verified_at)
                                                        <span class="badge bg-success ms-2">ตรวจสอบแล้ว</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">ไม่มีไฟล์แนบ</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- 4) บัญชีเข้าใช้งาน + PDPA --}}
                        <div class="col-12">
                            <h6 class="mb-2"><i class="ph-duotone ph-lock-key me-1"></i>4) บัญชีเข้าใช้งาน และ PDPA</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="mb-2"><strong>Username:</strong> {{ $user->username ?? '-' }}</div>
                                        <div class="mb-0"><strong>สิทธิ์ปัจจุบัน:</strong>
                                            @php $roleName = $user->getRoleNames()->first(); @endphp
                                            <span class="badge bg-info">{{ $roleName ?: '—' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="mb-2">
                                            <strong>PDPA:</strong>
                                            @if (!empty($user->pdpa_version))
                                                <span class="badge bg-success">ยอมรับแล้ว</span>
                                                <span class="small text-muted ms-2">{{ optional($user->pdpa_accepted_at)->format('d/m/Y H:i') }}</span>
                                            @else
                                                <span class="badge bg-secondary">ยังไม่ยอมรับ</span>
                                            @endif
                                        </div>
                                        <div class="mb-0">
                                            <strong>สถานะการลงทะเบียน:</strong>
                                            @php $st = $user->reg_status ?? 'รอตรวจสอบ'; @endphp
                                            <span class="badge {{ $st === 'อนุมัติ' ? 'bg-success' : ($st === 'ไม่อนุมัติ' ? 'bg-danger' : 'bg-warning') }}">
                                                {{ $st }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div> {{-- /row --}}
                </div>
            </div>
        </div>

        {{-- ===== กล่องสำหรับตรวจสอบ (ฟอร์มสั้น ส่งเฉพาะฟิลด์อนุมัติ) ===== --}}
        <div class="col-12">
            <form id="appReviewForm" method="POST" action="{{ route('backend.application-review.update', $user) }}" class="mb-3">
                @csrf
                @method('PUT')

                {{-- Hidden fields ที่จำเป็นให้ validation ผ่าน โดยดึงจากข้อมูลเดิม --}}
                {{-- 1) วัตถุประสงค์การลงทะเบียน --}}
                @foreach ($selectedLabels ?? [] as $lbl)
                    <input type="hidden" name="reg_purpose[]" value="{{ $lbl }}">
                @endforeach

                {{-- 1.1 จังหวัด/เขต สำหรับบทบาทกำกับดูแล --}}
                <input type="hidden" name="reg_supervise_province_code" value="{{ $user->reg_supervise_province_code }}">
                <input type="hidden" name="reg_supervise_region_id" value="{{ $user->reg_supervise_region_id }}">

                {{-- 2) หน่วยบริการ/หน่วยงาน เฉพาะเมื่อเป็นหน่วยบริการ --}}
                @php $isT = in_array('หน่วยบริการสุขภาพผู้เดินทาง', $selectedLabels ?? [], true); @endphp
                @if ($isT)
                    <input type="hidden" name="org_name" value="{{ $unit->org_name }}">
                    <input type="hidden" name="org_affiliation" value="{{ $unit->org_affiliation }}">
                    <input type="hidden" name="org_affiliation_other" value="{{ $unit->org_affiliation_other }}">
                    <input type="hidden" name="org_tel" value="{{ $unit->org_tel }}">
                    <input type="hidden" name="org_address" value="{{ $unit->org_address }}">
                    <input type="hidden" name="org_lat" value="{{ $unit->org_lat }}">
                    <input type="hidden" name="org_lng" value="{{ $unit->org_lng }}">
                    <input type="hidden" name="org_province_code" value="{{ $unit->org_province_code }}">
                    <input type="hidden" name="org_district_code" value="{{ $unit->org_district_code }}">
                    <input type="hidden" name="org_subdistrict_code" value="{{ $unit->org_subdistrict_code }}">
                    <input type="hidden" name="org_postcode" value="{{ $unit->org_postcode }}">
                    <input type="hidden" name="org_working_hours" value="{{ $unit->org_working_hours }}">
                    <input type="hidden" name="working_hours_json" value='@json($unit->org_working_hours_json ?? [], JSON_UNESCAPED_UNICODE)'>
                @else
                    @php $blankWh = ['mon'=>[],'tue'=>[],'wed'=>[],'thu'=>[],'fri'=>[],'sat'=>[],'sun'=>[]]; @endphp
                    <input type="hidden" name="working_hours_json" value='@json($blankWh, JSON_UNESCAPED_UNICODE)'>
                @endif

                {{-- 3) ผู้ลงทะเบียน --}}
                <input type="hidden" name="contact_cid" value="{{ $user->contact_cid }}">
                <input type="hidden" name="contact_name" value="{{ $user->contact_name }}">
                <input type="hidden" name="contact_position" value="{{ $user->contact_position }}">
                <input type="hidden" name="contact_mobile" value="{{ $user->contact_mobile }}">
                <input type="hidden" name="email" value="{{ $user->email }}">

                {{-- 4) บัญชีผู้ใช้ --}}
                <input type="hidden" name="username" value="{{ $user->username }}">

                {{-- 5) PDPA --}}
                <input type="hidden" name="pdpa_accept" value="1">

                {{-- ===== กล่องตรวจสอบจริง ===== --}}
                <x-register.admin-review :user="$user" :roles="$roles" />

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy"></i> บันทึกผลการตรวจสอบ
                    </button>
                    <a href="{{ route('backend.application-review.index') }}" class="btn btn-secondary">กลับรายการ</a>
                    <a href="{{ route('backend.application-review.edit', $user) }}" class="btn btn-outline-dark ms-auto">
                        <i class="ti ti-edit"></i> แก้ไขรายละเอียดเต็ม
                    </a>
                </div>
            </form>
        </div>

    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const el = document.getElementById('unit-mini-map');
            if (!el) return;

            const lat = {{ $unit->org_lat ?? 'null' }};
            const lng = {{ $unit->org_lng ?? 'null' }};
            if (lat === null || lng === null) return;

            const map = L.map(el, {
                scrollWheelZoom: false,
                zoomControl: true
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const pos = [lat, lng];
            L.marker(pos).addTo(map);
            map.setView(pos, 14);
            setTimeout(() => map.invalidateSize(), 300);
        });
    </script>
@endpush
