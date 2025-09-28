{{-- resources/views/backend/assessment/index.blade.php --}}
@extends('layouts.main')

@section('title', 'การประเมินตนเอง')
@section('breadcrumb-item', 'หน่วยบริการ')
@section('breadcrumb-item-active', 'การประเมินตนเอง')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0">รอบการประเมิน</h5>
                    <div class="btn-group">
                        <a href="{{ route('backend.assessment.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus"></i> เริ่มรอบประเมิน
                        </a>
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
                                    <th class="text-end">การทำงาน</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $i => $row)
                                    <tr>
                                        <td>{{ $rows->firstItem() + $i }}</td>
                                        <td>{{ $row->fiscalYear }}</td>
                                        <td>{{ $row->round }}</td>
                                        <td>{{ $row->serviceUnit->unitName ?? '-' }}</td>
                                        <td>
                                            @php $badge=['basic'=>'secondary','medium'=>'warning','advanced'=>'success'][$row->level] ?? 'secondary'; @endphp
                                            <span class="badge bg-{{ $badge }}">
                                                {{ ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'][$row->level] }}
                                            </span>
                                        </td>
                                        <td>
                                            @php $st=['draft'=>'info','submitted'=>'primary','reviewed'=>'success'][$row->status] ?? 'secondary'; @endphp
                                            <span class="badge bg-{{ $st }}">{{ strtoupper($row->status) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('backend.assessment.fill', $row) }}" class="avtar avtar-xs btn-link-secondary" title="กรอกแบบ">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>
                                            <a href="{{ route('backend.assessment.show', $row) }}" class="avtar avtar-xs btn-link-secondary" title="ดูสรุป">
                                                <i class="ti ti-eye f-20"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">ยังไม่มีข้อมูล</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($rows->hasPages())
                    <div class="card-footer">{!! $rows->links() !!}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
