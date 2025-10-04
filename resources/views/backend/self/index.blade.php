{{-- resources/views/backend/self/index.blade.php --}}
@extends('layouts.main')

@section('title', 'การประเมินตนเองของหน่วยบริการ')
@section('breadcrumb-item', 'การประเมินตนเอง')
@section('breadcrumb-item-active', 'รายการประเมิน')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">รายการแบบประเมินตนเอง</h5>
                    <a href="{{ route('backend.self.create', ['level_code' => session('current_service_level') ?? 'basic']) }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i> เริ่มการประเมิน
                    </a>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form class="row g-2 mb-3" method="get">
                        <div class="col-md-2">
                            <select name="year" class="form-select">
                                @foreach (range(now()->year + 543, now()->year + 538) as $y)
                                    <option value="{{ $y }}" @selected(request('year') == $y)>
                                        ปีงบประมาณ {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="round" class="form-select">
                                <option value="">-- รอบ --</option>
                                @for ($i = 1; $i <= 2; $i++)
                                    <option value="{{ $i }}" @selected(request('round') == $i)>รอบ {{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">-- สถานะ --</option>
                                @foreach (['draft' => 'ร่าง', 'submitted' => 'ส่งแล้ว', 'reviewing' => 'กำลังทบทวน', 'approved' => 'อนุมัติ', 'rejected' => 'ส่งกลับ'] as $k => $v)
                                    <option value="{{ $k }}" @selected(request('status') == $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="q" class="form-control" placeholder="ค้นหาหน่วยบริการ..." value="{{ request('q') }}">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn btn-outline-primary flex-fill"><i class="ph-magnifying-glass"></i> ค้นหา</button>
                            <a href="{{ route('backend.self.index') }}" class="btn btn-light flex-fill">ล้าง</a>
                        </div>
                    </form>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table align-middle table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>ปี / รอบ</th>
                                    <th>ระดับ</th>
                                    <th>สถานะ</th>
                                    <th>วันที่ส่ง</th>
                                    <th>วันที่ทบทวน</th>
                                    <th width="15%">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($forms as $i => $rs)
                                    <tr>
                                        <td>{{ $forms->firstItem() + $i }}</td>
                                        <td>{{ $rs->assess_year }} / {{ $rs->assess_round }}</td>
                                        <td>{{ strtoupper($rs->level_code) }}</td>
                                        <td>
                                            @php
                                                $statusLabel =
                                                    [
                                                        'draft' => 'ร่าง',
                                                        'submitted' => 'ส่งแล้ว',
                                                        'reviewing' => 'กำลังทบทวน',
                                                        'approved' => 'อนุมัติ',
                                                        'rejected' => 'ส่งกลับ',
                                                    ][$rs->status] ?? $rs->status;
                                                $badgeClass =
                                                    [
                                                        'draft' => 'bg-secondary',
                                                        'submitted' => 'bg-info',
                                                        'reviewing' => 'bg-warning',
                                                        'approved' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                    ][$rs->status] ?? 'bg-light';
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>{{ optional($rs->submitted_at)->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ optional($rs->reviewed_at)->format('d/m/Y') ?? '-' }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('backend.self.show', $rs->id) }}" class="btn btn-outline-secondary" title="ดูรายละเอียด">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                                @if ($rs->status === 'draft')
                                                    <a href="{{ route('backend.self.edit', $rs->id) }}" class="btn btn-outline-primary" title="แก้ไข">
                                                        <i class="ti ti-pencil"></i>
                                                    </a>
                                                    <form method="post" action="{{ route('backend.self.destroy', $rs->id) }}" onsubmit="return confirm('ยืนยันการลบ?')" class="d-inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="ลบ">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">ไม่มีข้อมูล</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $forms->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
