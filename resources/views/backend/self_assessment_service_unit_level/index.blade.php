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

        $levelTxt = ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'];
        $levelBg = ['basic' => 'info', 'medium' => 'warning', 'advanced' => 'danger'];

        // ถ้าจะโชว์สถานะการกรอก (draft/completed) ค่อยเปิดใช้ภายหลัง
        $statusBg = ['draft' => 'info', 'completed' => 'success'];

        // ✅ อัปเดตให้รองรับ reviewing และ returned
        $approvalTxt = [
            'pending' => 'รอดำเนินการ',
            'reviewing' => 'อยู่ระหว่างการพิจารณา',
            'returned' => 'ส่งกลับแก้ไข',
            'approved' => 'อนุมัติ',
            'rejected' => 'ไม่อนุมัติ',
        ];
        $approvalBg = [
            'pending' => 'secondary',
            'reviewing' => 'info',
            'returned' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
        ];
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
                                    <th>สถานะ</th>
                                    <th class="text-center">การอนุมัติ</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $i => $row)
                                    @php
                                        $ap = $row->approval_status;
                                        $locked = in_array($ap, ['pending', 'reviewing', 'approved', 'rejected'], true);
                                        $canEdit = !$locked; // แก้ไข/ทำแบบประเมินได้เมื่อยังไม่ส่ง หรือถูกส่งกลับ (returned)
                                    @endphp
                                    <tr>
                                        <td>{{ $rows->firstItem() + $i }}</td>
                                        <td>{{ $row->assess_year ? (int) $row->assess_year + 543 : '—' }}</td>
                                        <td>{{ (int) $row->assess_round === 1 ? 'รอบที่ 1' : 'รอบที่ 2' }}</td>
                                        <td>{{ optional($row->serviceUnit)->org_name ?? '—' }}</td>
                                        <td class="text-center">
                                            <x-level-badge :level="$row->level" />
                                        </td>

                                        <td>
                                            @php $st = $row->status; @endphp
                                            <span class="badge bg-{{ $statusBg[$row->status] ?? 'secondary' }}">
                                                {{ $row->status_text }}
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-{{ $approvalBg[$ap] ?? 'secondary' }}">
                                                {{ $approvalTxt[$ap] ?? '—' }}
                                            </span>
                                        </td>

                                        <td class="text-end">
                                            {{-- ดูสรุป --}}
                                            <a href="{{ route('backend.self-assessment-service-unit-level.show', $row->id) }}" class="avtar avtar-xs btn-link-primary me-1" data-bs-toggle="tooltip" data-bs-title="ดูสรุปผลการประเมิน">
                                                <i class="ti ti-eye f-20"></i>
                                            </a>

                                            {{-- ทำแบบประเมิน 6 องค์ประกอบ (แก้ไข/ทำต่อ) --}}
                                            {{-- @if ($canEdit)
                                                <button type="button" class="avtar avtar-xs btn-link-success me-1 js-assess" data-url="{{ route('backend.self-assessment-component.create', $row->id) }}" data-title="ทำแบบประเมิน 6 องค์ประกอบ" data-text="คุณต้องการเข้าสู่การทำแบบประเมิน 6 องค์ประกอบ สำหรับหน่วยบริการนี้ใช่ไหม?" data-confirm="เข้าสู่แบบประเมิน" data-bs-toggle="tooltip" data-bs-title="ทำแบบประเมิน 6 องค์ประกอบ">
                                                    <i class="ti ti-clipboard-list f-20"></i>
                                                </button>
                                            @else
                                                <button type="button" class="avtar avtar-xs btn-link-secondary disabled me-1 js-locked" data-reason="รายการนี้ถูกส่งแล้ว / อยู่ระหว่างตรวจ / ปิดจบแล้ว จึงไม่สามารถทำแบบประเมินได้" data-bs-toggle="tooltip" data-bs-title="ไม่สามารถทำแบบประเมินได้">
                                                    <i class="ti ti-clipboard-list f-20"></i>
                                                </button>
                                            @endif --}}

                                            {{-- แก้ไขแบบประเมินระดับหน่วยบริการ --}}
                                            @if ($canEdit)
                                                <button type="button" class="avtar avtar-xs btn-link-secondary me-1 js-edit-step1" data-url="{{ route('backend.self-assessment-service-unit-level.edit', $row->id) }}" data-title="แก้ไขแบบประเมิน" data-text="คุณต้องการเข้าไปแก้ไขแบบประเมินของหน่วยบริการนี้ใช่ไหม?" data-confirm="ไปหน้าแก้ไข" data-bs-toggle="tooltip" data-bs-title="แก้ไขแบบประเมิน">
                                                    <i class="ti ti-edit f-20"></i>
                                                </button>
                                            @else
                                                <button type="button" class="avtar avtar-xs btn-link-secondary me-1 js-locked" data-reason="แบบประเมินนี้อยู่ในสถานะรอดำเนินการตรวจสอบของ สคร./สสจ." data-bs-toggle="tooltip" data-bs-title="ไม่สามารถแก้ไขแบบประเมินได้">
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
                                                <button type="button" class="avtar avtar-xs btn-link-secondary js-locked" data-reason="รายการที่ส่งแล้วไม่สามารถลบได้" data-bs-toggle="tooltip" data-bs-title="ไม่สามารถลบได้">
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
            const btnAssess = e.target.closest('.js-assess');
            const btnEdit = e.target.closest('.js-edit-step1');
            const btnDelete = e.target.closest('.js-delete');
            const btnLocked = e.target.closest('.js-locked');

            // ✅ ข้อความกลางที่ต้องการใช้เหมือนกันทุกปุ่ม
            const REVIEW_MSG = 'แบบประเมินนี้อยู่ในสถานะรอดำเนินการตรวจสอบของ<br>สคร./สสจ.';



            // 🛑 ถ้าปุ่มไหนถูกล็อก ให้ขึ้นข้อความเดียวกันทุกปุ่ม
            if (btnLocked) {
                e.preventDefault();
                Swal.fire({
                    icon: 'info',
                    title: 'ไม่สามารถทำรายการได้',
                    html: REVIEW_MSG,
                    confirmButtonText: 'ตกลง',
                });
                return;
            }

            // 🧩 helper: ตรวจว่า row นี้ถูกล็อกไหม (กรณีอยากใช้ data-locked บน <tr>)
            const tr = e.target.closest('tr');
            const isRowLocked = tr?.dataset?.locked === '1';

            // ถ้า row ถูกล็อก แต่เผลอมีปุ่ม action โผล่มา ให้เด้งข้อความเดียวกัน
            if ((btnAssess || btnEdit || btnDelete) && isRowLocked) {
                e.preventDefault();
                Swal.fire({
                    icon: 'info',
                    title: 'ไม่สามารถทำรายการได้',
                    text: REVIEW_MSG,
                    confirmButtonText: 'ตกลง',
                });
                return;
            }

            // ── ปกติ: เข้าหน้าแบบประเมิน
            if (btnAssess) {
                e.preventDefault();
                const url = btnAssess.dataset.url;
                Swal.fire({
                    icon: 'question',
                    title: btnAssess.dataset.title ?? 'ยืนยันการเข้าสู่ฟอร์มแบบประเมิน 6 องค์ประกอบ',
                    text: btnAssess.dataset.text ?? 'คุณต้องการเข้าสู่หน้าแบบประเมินหรือไม่?',
                    showCancelButton: true,
                    confirmButtonText: btnAssess.dataset.confirm ?? 'ตกลง',
                    cancelButtonText: 'ยกเลิก',
                }).then(res => {
                    if (res.isConfirmed) window.location.href = url;
                });
                return;
            }

            // ── ปกติ: ไปหน้าแก้ไข Step 1
            if (btnEdit) {
                e.preventDefault();
                const url = btnEdit.dataset.url;
                Swal.fire({
                    icon: 'question',
                    title: btnEdit.dataset.title || 'ยืนยันการเข้าสู่ฟอร์มพิจารณาสถานะหน่วยบริการ',
                    text: btnEdit.dataset.text || 'คุณต้องการเข้าสู่หน้าแบบประเมินหรือไม่?',
                    showCancelButton: true,
                    confirmButtonText: btnEdit.dataset.confirm || 'ตกลง',
                    cancelButtonText: 'ยกเลิก',
                }).then(res => {
                    if (res.isConfirmed) window.location.href = url;
                });
                return;
            }

            // ── ปกติ: ยืนยันการลบ
            if (btnDelete) {
                e.preventDefault();
                const formId = btnDelete.dataset.form;
                const name = btnDelete.dataset.name || 'รายการนี้';
                const form = document.getElementById(formId);

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
