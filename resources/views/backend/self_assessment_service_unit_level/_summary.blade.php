{{-- resources/views/backend/self_assessment_service_unit_level/_summary.blade.php --}}
@php
    // หน่วยบริการ (ยึดตาม record ถ้ามี)
    $currentUnitName = $row->serviceUnit->org_name ?? '-';
    $oldLv = $row->level ?? null;
    $yearBE = $yearBE ?? ($row->assess_year ?? date('Y')) + 543;
    $roundTxt = isset($row->assess_round) ? fiscalRoundText((int) $row->assess_round) : '-';
@endphp

<div class="border rounded p-2 mb-3 bg-body-tertiary d-flex flex-wrap align-items-center gap-3">
    <div class="d-inline-flex align-items-center gap-2">
        <i class="ph-duotone ph-hospital fs-5"></i>
        <span class="text-muted">หน่วยบริการ</span>
        <span class="fw-semibold">{{ $currentUnitName }}</span>
    </div>
    <div class="vr"></div>
    <div class="d-inline-flex align-items-center gap-2">
        <i class="ph-duotone ph-medal fs-5"></i>
        <span class="text-muted">ระดับ</span>
        <span id="summaryLevelBadge">
            @if ($oldLv)
                <x-level-badge :level="$oldLv" class="ms-1" />
            @else
                <span class="badge bg-secondary ms-1">—</span>
            @endif
        </span>
    </div>
    <div class="vr"></div>
    <div class="d-inline-flex align-items-center gap-2">
        <i class="ph-duotone ph-calendar-blank fs-5"></i>
        <span class="text-muted">ปีงบประมาณ</span>
        <span class="fw-semibold">{{ $yearBE }}</span>
    </div>
    <div class="vr"></div>
    <div class="d-inline-flex align-items-center gap-2">
        <i class="ph-duotone ph-number-circle-one fs-5"></i>
        <span class="text-muted">รอบ</span>
        <span class="fw-semibold">{{ $roundTxt }}</span>
    </div>
</div>

{{-- ตารางองค์ประกอบ 1–6 + ข้อเสนอ/แผนพัฒนา (ยกมาเหมือนเดิม) --}}
@include('backend.self_assessment_service_unit_level._summary_table', ['components' => $components, 'form' => $form])
