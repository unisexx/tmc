@extends('layouts.main')

@section('title', 'จัดการหน่วยบริการ')
@section('breadcrumb-item', 'หน่วยบริการ')
@section('breadcrumb-item-active', 'รายการหน่วยบริการ')

@section('content')
    <div class="card">
        {{-- <div class="card-header d-flex align-items-center justify-content-between py-3">
            <h5 class="mb-0">รายการหน่วยบริการสุขภาพผู้เดินทาง</h5>
            <a href="{{ route('backend.service-unit.create') }}" class="btn btn-primary">
                <i class="ti ti-plus"></i> เพิ่มหน่วยบริการ
            </a>
        </div> --}}

        <div class="card-body pt-3">

            {{-- ฟอร์มค้นหา --}}
            <form method="GET" action="{{ route('backend.service-unit.index') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">

                {{-- ฝั่งซ้าย: ช่องค้นหาและตัวกรอง --}}
                <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    <div class="input-group" style="max-width: 300px;">
                        <span class="input-group-text">คำค้น</span>
                        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="ชื่อหน่วย / ที่อยู่ / โทรศัพท์">
                    </div>

                    <div class="input-group" style="max-width: 220px;">
                        <span class="input-group-text">จังหวัด</span>
                        <select name="province" class="form-select">
                            <option value="">— ทั้งหมด —</option>
                            @foreach ($provinces as $code => $name)
                                <option value="{{ $code }}" @selected($code == $provinceCode)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="input-group" style="max-width: 220px;">
                        <span class="input-group-text">สังกัด</span>
                        <select name="affiliation" class="form-select">
                            <option value="">— ทั้งหมด —</option>
                            <option value="กรมควบคุมโรค" @selected($affiliation == 'กรมควบคุมโรค')>กรมควบคุมโรค</option>
                            <option value="กรมการแพทย์" @selected($affiliation == 'กรมการแพทย์')>กรมการแพทย์</option>
                            <option value="เอกชน" @selected($affiliation == 'เอกชน')>เอกชน</option>
                            <option value="อื่นๆ" @selected($affiliation == 'อื่นๆ')>อื่นๆ</option>
                        </select>
                    </div>

                    {{-- ปุ่มค้นหา อยู่ต่อท้ายสังกัด --}}
                    <button class="btn btn-outline-primary">
                        <i class="ti ti-search"></i> ค้นหา
                    </button>
                </div>

                {{-- ฝั่งขวา: ปุ่มเพิ่ม --}}
                <div>
                    <a href="{{ route('backend.service-unit.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i> เพิ่มหน่วยบริการ
                    </a>
                </div>
            </form>




            {{-- ตาราง --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>ชื่อหน่วยบริการ</th>
                            <th>สังกัด</th>
                            <th>จังหวัด</th>
                            <th>เบอร์โทร</th>
                            <th class="text-center">แผนที่</th>
                            <th class="text-end">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($serviceUnits as $unit)
                            <tr>
                                <td>{{ $loop->iteration + ($serviceUnits->firstItem() - 1) }}</td>
                                <td>
                                    <strong>{{ $unit->org_name }}</strong><br>
                                    <small class="text-muted">{{ $unit->org_address }}</small>
                                </td>
                                <td>{{ $unit->org_affiliation ?: '-' }}</td>
                                <td>{{ $unit->province?->title }}</td>
                                <td>{{ $unit->org_tel ?: '-' }}</td>
                                <td class="text-center">
                                    @if ($unit->org_lat && $unit->org_lng)
                                        <a href="https://maps.google.com/?q={{ $unit->org_lat }},{{ $unit->org_lng }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <i class="ti ti-map-pin"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('backend.service-unit.edit', $unit) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <form action="{{ route('backend.service-unit.destroy', $unit) }}" method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบหน่วยบริการนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">ไม่พบข้อมูลหน่วยบริการ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $serviceUnits->links() }}
            </div>
        </div>
    </div>
@endsection
