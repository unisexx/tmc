@extends('layouts.main')

@section('title', 'การประเมินตนเอง')
@section('breadcrumb-item', 'หน่วยบริการ')
@section('breadcrumb-item-active', 'การประเมินตนเอง')

@section('content')
    @php
        $yearCE = fiscalYearCE();
        $roundNow = fiscalRound();
        $yearOpts = fiscalYearOptionsBE(5);
        $filterYear = request('year', $yearCE);
        $filterRound = (int) request('round', $roundNow);
    @endphp

    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="row justify-content-between align-items-center mb-3 g-3">
                        <div class="col">
                            <form method="GET" action="{{ route('backend.self-assessment-service-unit-level.index') }}" class="d-flex flex-wrap align-items-center gap-2">
                                <div class="input-group" style="max-width: 260px;">
                                    <span class="input-group-text">ปีงบประมาณ</span>
                                    <select name="year" class="form-select">
                                        @foreach ($yearOpts as $y)
                                            <option value="{{ $y['ce'] }}" @selected($filterYear == $y['ce'])>{{ $y['be'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="input-group" style="max-width: 280px;">
                                    <span class="input-group-text">รอบ</span>
                                    <select name="round" class="form-select">
                                        <option value="1" @selected($filterRound === 1)>รอบที่ 1 (ต.ค. – มี.ค.)</option>
                                        <option value="2" @selected($filterRound === 2)>รอบที่ 2 (เม.ย. – ก.ย.)</option>
                                    </select>
                                </div>

                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                                </button>
                            </form>
                        </div>

                        <div class="col-auto">
                            <a href="{{ route('backend.self-assessment-service-unit-level.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus"></i> เริ่มรอบประเมิน
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ปีงบประมาณ</th>
                                    <th>รอบ</th>
                                    <th>หน่วยบริการ</th>
                                    <th class="text-center">ระดับ</th>
                                    <th class="text-center">สถานะแบบประเมิน</th>
                                    <th class="text-center">การอนุมัติ</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $i => $row)
                                    @php
                                        $locked = $row->is_locked; // จาก accessor
                                        $canEdit = $row->can_edit;
                                    @endphp
                                    <tr data-locked="{{ $locked ? 1 : 0 }}">
                                        <td>{{ $rows->firstItem() + $i }}</td>
                                        <td>{{ $row->assess_year ? (int) $row->assess_year + 543 : '—' }}</td>
                                        <td>{{ (int) $row->assess_round === 1 ? 'รอบที่ 1' : 'รอบที่ 2' }}</td>
                                        <td>
                                            @php $su = $row->serviceUnit; @endphp
                                            <div class="fw-semibold">{{ $su->org_name ?? '—' }}</div>
                                            @if ($su)
                                                <div class="text-muted small">
                                                    {{ $su->province->title ?? '—' }}
                                                    @if (!empty($su->district?->title))
                                                        / {{ $su->district->title }}
                                                    @endif
                                                    @if (!empty($su->subdistrict?->title))
                                                        / {{ $su->subdistrict->title }}
                                                    @endif
                                                    @if (!empty($su->org_postcode))
                                                        · {{ $su->org_postcode }}
                                                    @endif
                                                </div>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            {{-- ใช้ component ระดับ --}}
                                            <x-level-badge :level="$row->level" />
                                            {{-- หรือถ้าอยากอิง accessor ล้วน ๆ:
                                            <span class="badge bg-{{ $row->level_badge_class }}">{{ $row->level_text ?? '-' }}</span> --}}
                                        </td>

                                        <td class="text-center">
                                            <x-status-badge :status="$row->status" />
                                            {{-- หรือ:
                                            <span class="badge bg-{{ $row->status_badge_class }}">{{ $row->status_text }}</span> --}}
                                        </td>

                                        <td class="text-center">
                                            <x-approval-badge :status="$row->approval_status" />
                                            {{-- หรือ:
                                            <span class="badge bg-{{ $row->approval_badge_class }}">{{ $row->approval_text ?? '—' }}</span> --}}
                                        </td>

                                        <td class="text-end">
                                            {{-- ดูสรุป --}}
                                            <a href="{{ route('backend.self-assessment-service-unit-level.show', $row->id) }}" class="avtar avtar-xs btn-link-primary me-1" data-bs-toggle="tooltip" data-bs-title="ดูสรุปผลการประเมิน">
                                                <i class="ti ti-eye f-20"></i>
                                            </a>

                                            {{-- แก้ไขแบบประเมินระดับหน่วยบริการ --}}
                                            @if ($canEdit)
                                                <button type="button" class="avtar avtar-xs btn-link-secondary me-1 js-edit-step1" data-url="{{ route('backend.self-assessment-service-unit-level.edit', $row->id) }}" data-title="แก้ไขแบบประเมิน" data-text="คุณต้องการเข้าไปแก้ไขแบบประเมินของหน่วยบริการนี้ใช่ไหม?" data-confirm="ไปหน้าแก้ไข" data-bs-toggle="tooltip" data-bs-title="แก้ไขแบบประเมิน">
                                                    <i class="ti ti-edit f-20"></i>
                                                </button>
                                            @else
                                                <button type="button" class="avtar avtar-xs btn-link-secondary me-1 js-locked" data-reason="แบบประเมินนี้อยู่ในสถานะรอดำเนินการตรวจสอบของ<br>สคร./สสจ." data-bs-toggle="tooltip" data-bs-title="ไม่สามารถแก้ไขแบบประเมินได้">
                                                    <i class="ti ti-edit f-20"></i>
                                                </button>
                                            @endif

                                            {{-- ลบ --}}
                                            @if ($canEdit)
                                                <form id="delete-form-{{ $row->id }}" action="{{ route('backend.self-assessment-service-unit-level.destroy', $row->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                                <button type="button" class="avtar avtar-xs btn-link-danger js-delete" data-form="delete-form-{{ $row->id }}" data-name="{{ optional($row->serviceUnit)->org_name }}" data-bs-toggle="tooltip" data-bs-title="ลบรายการ">
                                                    <i class="ti ti-trash f-20"></i>
                                                </button>
                                            @else
                                                <button type="button" class="avtar avtar-xs btn-link-secondary js-locked" data-reason="รายการที่ส่งตรวจสอบแล้วไม่สามารถลบได้" data-bs-toggle="tooltip" data-bs-title="ไม่สามารถลบได้">
                                                    <i class="ti ti-trash f-20"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">ยังไม่มีข้อมูล</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($rows->hasPages())
                    <div class="card-footer">{!! $rows->appends(request()->query())->links() !!}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('click', function(e) {
            const btnLocked = e.target.closest('.js-locked');
            const btnEdit = e.target.closest('.js-edit-step1');
            const btnDelete = e.target.closest('.js-delete');

            // ✅ ถ้ากดปุ่มที่ถูกล็อก (แก้ไข/ลบไม่ได้)
            if (btnLocked) {
                e.preventDefault();

                const reason = btnLocked.dataset.reason ||
                    'รายการนี้ถูกล็อก ไม่สามารถดำเนินการได้';

                Swal.fire({
                    icon: 'info',
                    title: 'ไม่สามารถทำรายการได้',
                    html: reason,
                    confirmButtonText: 'ตกลง',
                });
                return;
            }

            // ✏️ ปุ่มแก้ไข (แบบประเมินระดับ)
            if (btnEdit) {
                e.preventDefault();

                const url = btnEdit.dataset.url;
                const title = btnEdit.dataset.title ?? 'ยืนยันการเข้าสู่ฟอร์มพิจารณาสถานะหน่วยบริการ';
                const text = btnEdit.dataset.text ?? 'คุณต้องการเข้าสู่หน้าแบบประเมินหรือไม่?';
                const confirmText = btnEdit.dataset.confirm ?? 'ตกลง';

                Swal.fire({
                    icon: 'question',
                    title,
                    html: text, // ← เดิมเป็น html, (ตัวแปรไม่มี) ให้ใช้ html: text
                    // หรือจะใช้ text: text ก็ได้ ถ้าไม่ต้องการ HTML tag
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: 'ยกเลิก',
                }).then(res => {
                    if (res.isConfirmed && url) {
                        window.location.href = url;
                    }
                });
                return;
            }


            // 🗑️ ปุ่มลบ
            if (btnDelete) {
                e.preventDefault();

                const formId = btnDelete.dataset.form;
                const form = document.getElementById(formId);
                const name = btnDelete.dataset.name ?? 'รายการนี้';

                Swal.fire({
                    icon: 'warning',
                    title: 'ยืนยันการลบ',
                    html: `ต้องการลบ <b>${name}</b> ใช่หรือไม่?<br><small>การลบไม่สามารถย้อนกลับได้</small>`,
                    showCancelButton: true,
                    confirmButtonText: 'ลบเลย',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#d33',
                }).then(res => {
                    if (res.isConfirmed && form) form.submit();
                });
                return;
            }
        }, false);
    </script>
@endpush
