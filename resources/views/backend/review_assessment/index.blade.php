@extends('layouts.main')

@section('title', 'ตรวจสอบผลการประเมิน')
@section('breadcrumb-item', 'การประเมิน')
@section('breadcrumb-item-active', 'ตรวจสอบผลการประเมิน')

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
                    {{-- ===================== FILTER BAR ===================== --}}
                    <div class="row justify-content-between align-items-center mb-3 g-3">
                        <div class="col">
                            <form method="GET" action="{{ route('backend.review-assessment.index') }}" class="d-flex flex-wrap align-items-center gap-2">
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

                                <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control" style="max-width: 220px;" placeholder="ค้นหาหน่วยบริการ...">

                                {{-- <select name="status" class="form-select" style="max-width: 160px;">
                                    <option value="">สถานะฟอร์ม</option>
                                    @foreach (config('assessment.status_text') as $key => $text)
                                        <option value="{{ $key }}" @selected(request('status') == $key)>{{ $text }}</option>
                                    @endforeach
                                </select> --}}

                                <select name="approval" class="form-select" style="max-width: 160px;">
                                    <option value="">สถานะตรวจสอบ</option>
                                    @foreach (config('assessment.approval_text') as $key => $text)
                                        <option value="{{ $key }}" @selected(request('approval') == $key)>{{ $text }}</option>
                                    @endforeach
                                </select>

                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- ===================== TABLE ===================== --}}
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ปีงบประมาณ</th>
                                    <th>รอบ</th>
                                    <th>หน่วยบริการ</th>
                                    <th class="text-center">ระดับ</th>
                                    <th class="text-center">สถานะฟอร์ม</th>
                                    <th class="text-center">การอนุมัติ</th>
                                    <th class="text-center" width="120">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $i => $row)
                                    @php
                                        $su = $row->serviceUnit;
                                    @endphp
                                    <tr>
                                        <td>{{ $items->firstItem() + $i }}</td>
                                        <td>{{ $row->assess_year ? $row->assess_year + 543 : '—' }}</td>
                                        <td>{{ $row->assess_round == 1 ? 'รอบที่ 1' : 'รอบที่ 2' }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $su->org_name ?? '—' }}</div>
                                            @if ($su)
                                                <div class="text-muted small">
                                                    {{ $su->geo_titles ?: '—' }}
                                                    @if (!empty($su->org_postcode))
                                                        · {{ $su->org_postcode }}
                                                    @endif
                                                </div>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <x-level-badge :level="$row->level" />
                                        </td>

                                        <td class="text-center">
                                            <x-status-badge :status="$row->status" />
                                        </td>

                                        <td class="text-center">
                                            <x-approval-badge :status="$row->approval_status" />
                                        </td>

                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center justify-content-center gap-2" role="group" aria-label="จัดการการตรวจสอบแบบประเมิน">

                                                {{-- ตรวจสอบแบบประเมิน --}}
                                                <a href="{{ route('backend.review-assessment.show', $row->id) }}" class="btn btn-sm btn-light border">
                                                    <i class="ti ti-file-search me-1"></i> ตรวจสอบแบบประเมิน
                                                </a>

                                                {{-- ลบข้อมูล --}}
                                                @can('delete', $row)
                                                    <form id="delete-form-{{ $row->id }}" action="{{ route('backend.review-assessment.destroy', $row->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>

                                                    <button type="button" class="btn btn-sm btn-light border js-delete" data-form="delete-form-{{ $row->id }}" data-name="{{ $su->org_name ?? 'รายการนี้' }}">
                                                        <i class="ti ti-trash me-1"></i> ลบข้อมูล
                                                    </button>
                                                @endcan

                                            </div>
                                        </td>



                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">ไม่พบข้อมูล</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($items->hasPages())
                    <div class="card-footer">{!! $items->appends(request()->query())->links() !!}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('click', function(e) {
            const btnDelete = e.target.closest('.js-delete');
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
            }
        }, false);
    </script>
@endpush
