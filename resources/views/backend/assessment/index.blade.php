@extends('layouts.main')

@section('title', 'การประเมินตนเอง')
@section('breadcrumb-item', 'หน่วยบริการ')
@section('breadcrumb-item-active', 'การประเมินตนเอง')

@section('content')
    @php
        // ใช้ helper functions ที่เราทำไว้
        $yearCE = fiscalYearCE();
        $roundNow = fiscalRound();
        $yearOpts = fiscalYearOptionsBE(5); // แสดง 5 ปี (ปรับได้)
        $filterYear = request('year', $yearCE);
        $filterRound = (int) request('round', $roundNow);

        // map สำหรับแสดงผล
        $levelTxt = ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'];
        $levelBg = ['basic' => 'info', 'medium' => 'warning', 'advanced' => 'danger']; // ฟ้า/เหลือง/แดง
        $statusBg = ['draft' => 'info', 'completed' => 'success'];
        $approvalTxt = ['pending' => 'รอดำเนินการ', 'approved' => 'อนุมัติ', 'rejected' => 'ไม่อนุมัติ'];
        $approvalBg = ['pending' => 'secondary', 'approved' => 'success', 'rejected' => 'danger'];
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header py-3">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <h5 class="mb-0">รอบการประเมิน</h5>

                        {{-- ฟิลเตอร์ ปีงบ & รอบ (GET) --}}
                        <form method="GET" action="{{ route('backend.assessment.index') }}" class="d-flex gap-2 align-items-center">
                            <div class="input-group">
                                <span class="input-group-text">ปีงบประมาณ</span>
                                <select name="year" class="form-select">
                                    @foreach ($yearOpts as $y)
                                        <option value="{{ $y['ce'] }}" @selected($filterYear == $y['ce'])>
                                            {{ $y['be'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">รอบ</span>
                                <select name="round" class="form-select">
                                    <option value="1" @selected($filterRound === 1)>รอบที่ 1 (ต.ค. – มี.ค.)</option>
                                    <option value="2" @selected($filterRound === 2)>รอบที่ 2 (เม.ย. – ก.ย.)</option>
                                </select>
                            </div>
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="ti ti-filter"></i> กรอง
                            </button>
                            <a href="{{ route('backend.assessment.index') }}" class="btn btn-outline-secondary">
                                ล้าง
                            </a>
                            <a href="{{ route('backend.assessment.step1.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus"></i> เริ่มรอบประเมิน
                            </a>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ปีงบประมาณ</th>
                                    <th>รอบ</th>
                                    <th>หน่วยบริการ</th>
                                    <th>ระดับ</th>
                                    <th>สถานะ</th>
                                    <th>การอนุมัติ</th>
                                    <th class="text-end">การทำงาน</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $i => $row)
                                    <tr>
                                        <td>{{ $rows->firstItem() + $i }}</td>

                                        {{-- ปีงบประมาณ แสดง พ.ศ. --}}
                                        <td>{{ ($row->assess_year ?? 0) + 543 }}</td>

                                        {{-- รอบ แสดงข้อความสั้น/ยาวได้ตามต้องการ --}}
                                        <td>
                                            {{ $row->assess_round == 1 ? 'รอบที่ 1' : 'รอบที่ 2' }}
                                        </td>

                                        <td>{{ $row->serviceUnit->unitName ?? '-' }}</td>

                                        {{-- ระดับ: ฟ้า/เหลือง/แดง --}}
                                        <td>
                                            @php $lv = $row->level; @endphp
                                            <span class="badge bg-{{ $levelBg[$lv] ?? 'secondary' }}">
                                                {{ $levelTxt[$lv] ?? '—' }}
                                            </span>
                                        </td>

                                        {{-- สถานะ: draft/completed --}}
                                        <td>
                                            @php $st = $row->status; @endphp
                                            <span class="badge bg-{{ $statusBg[$st] ?? 'secondary' }}">
                                                {{ strtoupper($st ?? '-') }}
                                            </span>
                                        </td>

                                        {{-- การอนุมัติ: pending/approved/rejected --}}
                                        <td>
                                            @php $ap = $row->approval_status; @endphp
                                            <span class="badge bg-{{ $approvalBg[$ap] ?? 'secondary' }}">
                                                {{ $approvalTxt[$ap] ?? '—' }}
                                            </span>
                                        </td>

                                        <td class="text-end">
                                            {{-- แก้ไข --}}
                                            <a href="{{ route('backend.assessment.edit', $row->id) }}" class="avtar avtar-xs btn-link-secondary" data-bs-toggle="tooltip" title="แก้ไข">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>

                                            {{-- ลบ --}}
                                            <form action="{{ route('backend.assessment.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบรายการนี้หรือไม่? การลบไม่สามารถย้อนกลับได้');">
                                                @csrf
                                                @method('DELETE')
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
