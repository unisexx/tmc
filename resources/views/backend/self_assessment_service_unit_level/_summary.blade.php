{{-- resources/views/backend/self_assessment_service_unit_level/_summary.blade.php --}}

<div class="border rounded p-2 mb-3 bg-body-tertiary d-flex flex-wrap align-items-center gap-3">
    <div class="d-inline-flex align-items-center gap-2">
        <i class="ph-duotone ph-hospital fs-5"></i>
        <span class="text-muted">หน่วยบริการ</span>
        <span class="fw-semibold">{{ $row->serviceUnit->org_name ?? '-' }}</span>
    </div>

    <div class="vr"></div>
    <div class="d-inline-flex align-items-center gap-2">
        <i class="ph-duotone ph-medal fs-5"></i>
        <span class="text-muted">ระดับ</span>
        <span id="summaryLevelBadge">
            @if (!empty($row->level))
                <x-level-badge :level="$row->level" class="ms-1" />
            @else
                <span class="badge bg-secondary ms-1">—</span>
            @endif
        </span>
    </div>

    <div class="vr"></div>
    <div class="d-inline-flex align-items-center gap-2">
        <i class="ph-duotone ph-calendar-blank fs-5"></i>
        <span class="text-muted">ปีงบประมาณ</span>
        <span class="fw-semibold">
            {{ isset($row->assess_year) ? $row->assess_year + 543 : (int) date('Y') + 543 }}
        </span>
    </div>

    <div class="vr"></div>
    <div class="d-inline-flex align-items-center gap-2">
        <i class="ph-duotone ph-number-circle-one fs-5"></i>
        <span class="text-muted">รอบ</span>
        <span class="fw-semibold">
            {{ isset($row->assess_round) ? fiscalRoundText((int) $row->assess_round) : '-' }}
        </span>
    </div>

    <div class="vr"></div>
    <div class="d-inline-flex align-items-center gap-2">
        <i class="ph-duotone ph-check-circle fs-5"></i>
        <span class="text-muted">ผลการประเมิน</span>
        <span id="summaryApprovalBadge">
            <x-approval-badge :status="$row->approval_status ?? null" class="ms-1" />
        </span>
    </div>
</div>
