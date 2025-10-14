@extends('layouts.main')

@section('title', 'ตั้งค่าบริการสำหรับการแสดงผลหน้าบ้าน')
@section('breadcrumb-item', 'Backend')
@section('breadcrumb-item-active', 'ตั้งค่าแสดงผล (รายหน่วย/รอบ)')

@section('content')
    @php
        $levelMap = ['basic' => 'พื้นฐาน', 'intermediate' => 'กลาง', 'advanced' => 'สูง'];
    @endphp

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="ph-duotone ph-sliders-horizontal me-1"></i>
                การตั้งค่าบริการของหน่วย: <strong>{{ $form->serviceUnit->org_name ?? '-' }}</strong>
                <span class="mx-2">|</span> ระดับ:
                <span class="badge text-bg-secondary">{{ $levelMap[$form->level_code] ?? $form->level_code }}</span>
            </div>
            <div class="text-muted small">
                ปีงบ {{ ($form->assess_year ?? date('Y')) + 543 }} • รอบ {{ $form->assess_round ?? '-' }}
            </div>
        </div>

        <form method="POST" action="{{ route('backend.assessment-forms.services.update', $form->id) }}">
            @csrf @method('PUT')
            <div class="card-body">
                <div class="row g-3">
                    @forelse($services as $svc)
                        <div class="col-12 col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="svc_{{ $svc->id }}" name="svc_{{ $svc->id }}" @checked($svc->resolved_enabled)>
                                <label class="form-check-label fw-semibold" for="svc_{{ $svc->id }}">{{ $svc->name }}</label>
                                @if ($svc->description)
                                    <div class="small text-muted">{{ $svc->description }}</div>
                                @endif
                                <div class="small {{ $svc->resolved_enabled ? 'text-success' : 'text-muted' }}">
                                    ค่าเริ่มต้น: {{ $svc->default_enabled ? 'เปิด' : 'ปิด' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">ยังไม่มีบริการสำหรับระดับนี้ โปรดเพิ่มในเมนู “บริการที่แสดงหน้าบ้าน”</div>
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">
                    <i class="ti ti-device-floppy me-1"></i> บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>
@endsection
