@extends('layouts.main')

@section('title', 'การประเมินตนเอง')
@section('breadcrumb-item', 'หน่วยบริการ')
@section('breadcrumb-item-active', 'การประเมินตนเอง')

@section('content')
    @php
        // ใช้ logic เดิมของไฟล์นี้
        $yearCE = fiscalYearCE();
        $roundNow = fiscalRound();
        $yearOpts = fiscalYearOptionsBE(5);

        $filterYear = (int) request('year', $yearCE);
        $filterRound = (int) request('round', $roundNow);
    @endphp

    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">

                    {{-- ========================= --}}
                    {{-- แถบค้นหา (ปีงบประมาณ / รอบ) --}}
                    {{-- ========================= --}}
                    <div class="row justify-content-between align-items-start mb-3 g-3">
                        <div class="col">
                            <form method="GET" action="{{ route('backend.self-assessment-service-unit-level.index') }}" class="d-flex flex-wrap align-items-stretch gap-2">

                                {{-- ปีงบประมาณ --}}
                                <div class="input-group" style="max-width:260px;">
                                    <span class="input-group-text">ปีงบประมาณ</span>
                                    <select id="filter-year" name="year" class="form-select">
                                        @foreach ($yearOpts as $y)
                                            <option value="{{ $y['ce'] }}" @selected($filterYear === (int) $y['ce'])>
                                                {{ $y['be'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- รอบ (จะถูกควบคุมโดย JS ตามปีที่เลือก) --}}
                                <div class="input-group" style="max-width:280px;">
                                    <span class="input-group-text">รอบ</span>
                                    <select id="filter-round" name="round" class="form-select">
                                        {{-- ใส่ option เริ่มต้นตามค่าปัจจุบัน เพื่อให้ไม่ error ตอนโหลดครั้งแรก
                                             JS จะ rebuild ให้ถูกต้องภายหลัง --}}
                                        <option value="1" @selected($filterRound === 1)>รอบที่ 1 (ต.ค. – มี.ค.)</option>
                                        <option value="2" @selected($filterRound === 2)>รอบที่ 2 (เม.ย. – ก.ย.)</option>
                                    </select>
                                </div>

                                {{-- ปุ่มค้นหา --}}
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                                </button>
                            </form>
                        </div>

                        {{-- ปุ่มเริ่มรอบประเมิน --}}
                        <div class="col-auto">
                            <a href="{{ route('backend.self-assessment-service-unit-level.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus"></i> เริ่มรอบประเมิน
                            </a>
                        </div>
                    </div>

                    {{-- ========================= --}}
                    {{-- ตารางรายการประเมินตนเอง --}}
                    {{-- ========================= --}}
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
                                    <th class="text-center" width="120">จัดการ</th>
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
                                            {{-- ระดับหน่วยบริการ --}}
                                            <x-level-badge :level="$row->level" />
                                            {{-- fallback:
                                            <span class="badge bg-{{ $row->level_badge_class }}">
                                                {{ $row->level_text ?? '-' }}
                                            </span>
                                            --}}
                                        </td>

                                        <td class="text-center">
                                            {{-- สถานะแบบประเมิน --}}
                                            <x-status-badge :status="$row->status" />
                                            {{-- fallback:
                                            <span class="badge bg-{{ $row->status_badge_class }}">
                                                {{ $row->status_text }}
                                            </span>
                                            --}}
                                        </td>

                                        <td class="text-center">
                                            {{-- สถานะการอนุมัติ --}}
                                            <x-approval-badge :status="$row->approval_status" />
                                            {{-- fallback:
                                            <span class="badge bg-{{ $row->approval_badge_class }}">
                                                {{ $row->approval_text ?? '—' }}
                                            </span>
                                            --}}
                                        </td>

                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="จัดการแบบประเมินหน่วยบริการ">

                                                {{-- ดูสรุป --}}
                                                <a href="{{ route('backend.self-assessment-service-unit-level.show', $row->id) }}" class="btn btn-sm btn-light border" data-bs-toggle="tooltip" data-bs-title="ดูสรุปผลการประเมิน">
                                                    <i class="ti ti-eye me-1"></i> ดูสรุป
                                                </a>

                                                {{-- แก้ไข --}}
                                                @if ($canEdit)
                                                    <button type="button" class="btn btn-sm btn-light border js-edit-step1" data-url="{{ route('backend.self-assessment-service-unit-level.edit', $row->id) }}" data-title="แก้ไขแบบประเมิน" data-text="คุณต้องการเข้าไปแก้ไขแบบประเมินของหน่วยบริการนี้ใช่ไหม?" data-confirm="ไปหน้าแก้ไข" data-bs-toggle="tooltip" data-bs-title="แก้ไขแบบประเมิน">
                                                        <i class="ti ti-edit me-1"></i> แก้ไข
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-light border text-muted js-locked" data-reason="รายการที่ส่งตรวจสอบแล้วไม่สามารถแก้ไขได้" data-bs-toggle="tooltip" data-bs-title="ไม่สามารถแก้ไขแบบประเมินได้">
                                                        <i class="ti ti-edit me-1"></i> แก้ไข
                                                    </button>
                                                @endif

                                                {{-- ลบ --}}
                                                @if ($canEdit)
                                                    <form id="delete-form-{{ $row->id }}" action="{{ route('backend.self-assessment-service-unit-level.destroy', $row->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-light border js-delete" data-form="delete-form-{{ $row->id }}" data-name="{{ optional($row->serviceUnit)->org_name }}" data-bs-toggle="tooltip" data-bs-title="ลบรายการ">
                                                        <i class="ti ti-trash me-1"></i> ลบ
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-light border text-muted js-locked" data-reason="รายการที่ส่งตรวจสอบแล้วไม่สามารถลบได้" data-bs-toggle="tooltip" data-bs-title="ไม่สามารถลบได้">
                                                        <i class="ti ti-trash me-1"></i> ลบ
                                                    </button>
                                                @endif

                                            </div>
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
                    <div class="card-footer">
                        {!! $rows->appends(request()->query())->links() !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        (function() {
            // ====== อ้างอิง element ปีงบและรอบ ======
            const yearEl = document.getElementById('filter-year');
            const roundEl = document.getElementById('filter-round');

            // ====== ค่าปัจจุบันจากเซิร์ฟเวอร์ (ฝั่ง PHP inject มา) ======
            const CURRENT_FY_CE = {{ (int) fiscalYearCE() }}; // ปีงบ ค.ศ. ปัจจุบัน
            const CURRENT_MONTH = {{ (int) now()->month }}; // เดือนปัจจุบัน 1-12
            const SELECTED_ROUND = {{ (int) $filterRound }}; // รอบที่เลือกอยู่ ณ ตอนโหลดหน้า

            // ฟังก์ชัน helper: สร้าง option list ใหม่ให้ <select>
            function setOptions(selectEl, items, placeholder = null) {
                const frag = document.createDocumentFragment();
                if (placeholder !== null) {
                    const first = document.createElement('option');
                    first.value = '';
                    first.textContent = placeholder;
                    frag.appendChild(first);
                }
                items.forEach(({
                    value,
                    text,
                    selected
                }) => {
                    const o = document.createElement('option');
                    o.value = value;
                    o.textContent = text;
                    if (selected) o.selected = true;
                    frag.appendChild(o);
                });
                selectEl.replaceChildren(frag);
            }

            // ตรรกะกำหนดจำนวน "รอบ" ที่อนุญาตให้เลือก ตามปีงบประมาณ
            // - ถ้าเป็นปีก่อนหน้า (< ปีงบปัจจุบัน): มี 2 รอบแน่นอน
            // - ถ้าเป็นปีงบปัจจุบัน:
            //      ถ้าเดือนปัจจุบันอยู่ช่วง ต.ค.(10)–มี.ค.(3) => inRound1 = true => มีแค่รอบ 1
            //      ถ้า เม.ย.(4)–ก.ย.(9) => inRound1 = false => มีครบ 2 รอบ
            // - ถ้าเป็นปีอนาคต (> ปีงบปัจจุบัน): ตอนนี้ถือว่ายังไม่เปิดรอบ (0)
            function roundsAvailableFor(yearCE) {
                if (yearCE < CURRENT_FY_CE) return 2;
                if (yearCE > CURRENT_FY_CE) return 0;
                const inRound1 = (CURRENT_MONTH >= 10 || CURRENT_MONTH <= 3);
                return inRound1 ? 1 : 2;
            }

            // สร้าง option ของ "รอบ" ใหม่ทุกครั้งที่ปีเปลี่ยน
            function rebuildRoundOptions() {
                const yearCE = parseInt(yearEl?.value || CURRENT_FY_CE, 10);
                const count = roundsAvailableFor(yearCE);

                // ปีอนาคต (ยังไม่มีรอบให้เลือก)
                if (count === 0) {
                    setOptions(roundEl, [], 'ไม่มีรอบให้เลือก');
                    roundEl.disabled = true;
                    return;
                }

                roundEl.disabled = false;

                // พยายามรักษาค่าเดิม ถ้าอยู่ในช่วง valid
                const keep = parseInt(roundEl.value || SELECTED_ROUND, 10);
                const chosen = (keep >= 1 && keep <= count) ? keep : count;

                const opts = [];
                if (count >= 1) {
                    opts.push({
                        value: '1',
                        text: 'รอบที่ 1 (ต.ค. – มี.ค.)',
                        selected: chosen === 1
                    });
                }
                if (count >= 2) {
                    opts.push({
                        value: '2',
                        text: 'รอบที่ 2 (เม.ย. – ก.ย.)',
                        selected: chosen === 2
                    });
                }

                setOptions(roundEl, opts);
            }

            // เมื่อผู้ใช้เปลี่ยน "ปีงบประมาณ" -> อัปเดต "รอบ"
            yearEl?.addEventListener('change', () => {
                rebuildRoundOptions();
            });

            // init ครั้งแรกหลังโหลดหน้า
            rebuildRoundOptions();

            // ====== ด้านล่าง: event สำหรับปุ่มในตาราง (lock/edit/delete) ======
            document.addEventListener('click', function(e) {
                const btnLocked = e.target.closest('.js-locked');
                const btnEdit = e.target.closest('.js-edit-step1');
                const btnDelete = e.target.closest('.js-delete');

                // รายการที่ล็อก (แก้ไข/ลบไม่ได้)
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

                // ปุ่มแก้ไข
                if (btnEdit) {
                    e.preventDefault();

                    const url = btnEdit.dataset.url;
                    const title = btnEdit.dataset.title ?? 'ยืนยันการเข้าสู่ฟอร์มพิจารณาสถานะหน่วยบริการ';
                    const text = btnEdit.dataset.text ?? 'คุณต้องการเข้าสู่หน้าแบบประเมินหรือไม่?';
                    const confirmText = btnEdit.dataset.confirm ?? 'ตกลง';

                    Swal.fire({
                        icon: 'question',
                        title,
                        html: text,
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

                // ปุ่มลบ
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
        })();
    </script>
@endpush
