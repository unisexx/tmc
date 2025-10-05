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
        $statusBg = ['draft' => 'info', 'completed' => 'success'];
        $approvalTxt = ['pending' => 'รอดำเนินการ', 'approved' => 'อนุมัติ', 'rejected' => 'ไม่อนุมัติ'];
        $approvalBg = ['pending' => 'secondary', 'approved' => 'success', 'rejected' => 'danger'];
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
                                    {{-- <th>สถานะ</th> --}}
                                    <th class="text-center">ประเมินตามเกณฑ์<br>6 องค์ประกอบ</th>
                                    <th class="text-center">การอนุมัติ</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $i => $row)
                                    <tr>
                                        <td>{{ $rows->firstItem() + $i }}</td>
                                        <td>{{ (int) $row->assess_year + 543 }}</td>
                                        <td>{{ (int) $row->assess_round === 1 ? 'รอบที่ 1' : 'รอบที่ 2' }}</td>
                                        <td>{{ optional($row->serviceUnit)->org_name ?? '—' }}</td>
                                        <td class="text-center">
                                            @php $lv = $row->level; @endphp
                                            <span class="badge bg-{{ $levelBg[$lv] ?? 'secondary' }}">{{ $levelTxt[$lv] ?? '—' }}</span>
                                        </td>
                                        {{-- <td>
                                            @php $st = $row->status; @endphp
                                            <span class="badge bg-{{ $statusBg[$st] ?? 'secondary' }}">{{ $st ?? '—' }}</span>
                                        </td> --}}
                                        <td class="text-center"></td>
                                        <td class="text-center">
                                            @php $ap = $row->approval_status; @endphp
                                            <span class="badge bg-{{ $approvalBg[$ap] ?? 'secondary' }}">{{ $approvalTxt[$ap] ?? '—' }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('backend.self-assessment-service-unit-level.edit', $row->id) }}" class="avtar avtar-xs btn-link-secondary" data-bs-toggle="tooltip" title="แก้ไข">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>
                                            <form action="{{ route('backend.self-assessment-service-unit-level.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบรายการนี้หรือไม่? การลบไม่สามารถย้อนกลับได้');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="avtar avtar-xs btn-link-danger" data-bs-toggle="tooltip" title="ลบ">
                                                    <i class="ti ti-trash f-20"></i>
                                                </button>
                                            </form>
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
