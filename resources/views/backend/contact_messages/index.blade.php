{{-- resources/views/backend/contact_messages/index.blade.php --}}
@extends('layouts.main')

@section('title', 'กล่องข้อความ')
@section('breadcrumb-item', 'จัดการข้อมูลหน้าแรก')
@section('breadcrumb-item-active', 'กล่องข้อความ')

@section('content')
    <div class="row">
        <div class="col-12">

            @php
                $query = $q ?? request('q');
                $status = request('status');
                $read = request('read');
            @endphp

            <div class="card">
                <div class="card-body">
                    {{-- Filter Bar: ย้ายสถานะ + การอ่านมาอยู่ฝั่งซ้าย --}}
                    <form method="GET" action="{{ route('backend.contact-messages.index') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">

                        {{-- ซ้าย: สถานะ + การอ่าน + คำค้น + ปุ่มค้นหา --}}
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="input-group" style="width: min(420px, 90vw);">
                                <span class="input-group-text">คำค้น</span>
                                <input id="q" type="text" name="q" value="{{ $query }}" class="form-control" placeholder="ชื่อ ผู้ส่ง อีเมล โทรศัพท์ หัวข้อ เนื้อหา">
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2">
                                {{-- Group 1: สถานะ --}}
                                <div class="input-group" style="width:auto; min-width:200px;">
                                    <label class="input-group-text" for="statusSelect">สถานะ</label>
                                    <select name="status" id="statusSelect" class="form-select" style="width:160px;">
                                        <option value="">ทุกสถานะ</option>
                                        <option value="new" @selected($status === 'new')>รอดำเนินการ</option>
                                        <option value="done" @selected($status === 'done')>ดำเนินการแล้ว</option>
                                    </select>
                                </div>

                                {{-- Group 2: การอ่าน --}}
                                <div class="input-group" style="width:auto; min-width:200px;">
                                    <label class="input-group-text" for="readSelect">การอ่าน</label>
                                    <select name="read" id="readSelect" class="form-select" style="width:160px;">
                                        <option value="">ทั้งหมด</option>
                                        <option value="unread" @selected($read === 'unread')>ยังไม่อ่าน</option>
                                        <option value="read" @selected($read === 'read')>อ่านแล้ว</option>
                                    </select>
                                </div>
                            </div>



                            <button class="btn btn-outline-primary" type="submit">
                                <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                            </button>
                        </div>
                    </form>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:72px;">#</th>
                                    <th>ผู้ส่ง</th>
                                    <th>หัวข้อ / เนื้อหา (ย่อ)</th>
                                    <th class="text-center" style="width:120px;">ช่องทาง</th>
                                    <th class="text-center" style="width:140px;">สถานะ</th>
                                    <th class="text-center" style="width:170px;">วันที่ส่ง</th>
                                    <th class="text-center" style="width:170px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rs as $i => $row)
                                    <tr @class(['table-warning' => is_null($row->read_at)])>
                                        <td>{{ method_exists($rs, 'firstItem') ? $rs->firstItem() + $i : $loop->iteration }}</td>
                                        <td class="align-top">
                                            <div class="row align-items-start g-2">
                                                {{-- ซ้าย: ไอคอนจดหมาย (เปิด/ปิด) แบบ avatar --}}
                                                <div class="col-auto pe-0">
                                                    <div class="avtar avtar-s {{ $row->read_at ? 'btn-light-secondary' : 'btn-light-danger' }}" data-bs-toggle="tooltip" data-bs-title="{{ $row->read_at ? 'อ่านแล้วเมื่อ ' . $row->read_at->format('d/m/Y H:i') : 'ยังไม่อ่าน' }}">
                                                        <i class="ph-duotone {{ $row->read_at ? 'ph-envelope-open' : 'ph-envelope-simple' }} f-18"></i>
                                                    </div>
                                                </div>

                                                {{-- ขวา: เนื้อหา --}}
                                                <div class="col">
                                                    <div class="fw-semibold text-truncate" title="{{ $row->name }}">{{ $row->name }}</div>
                                                    <div class="small text-muted text-truncate" title="{{ trim($row->email . ($row->phone ? ' · ' . $row->phone : '')) }}">
                                                        <i class="ti ti-mail"></i> {{ $row->email }}
                                                        @if ($row->phone)
                                                            · <i class="ti ti-phone"></i> {{ $row->phone }}
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $row->subject }}</div>
                                            <small class="text-muted d-block">
                                                {{ \Illuminate\Support\Str::limit(strip_tags($row->message), 120) }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            เว็บฟอร์ม
                                        </td>
                                        <td class="text-center">
                                            @if ($row->status === 'done')
                                                <span class="badge border border-success text-success-emphasis bg-success-light shadow-sm">
                                                    <i class="ph-duotone ph-check-circle"></i> ดำเนินการแล้ว
                                                </span>
                                            @else
                                                <span class="badge border border-warning text-warning-emphasis bg-warning-light shadow-sm">
                                                    <i class="ph-duotone ph-envelope-open"></i> รอดำเนินการ
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ $row->created_at?->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="จัดการข้อมูล">
                                                <a href="{{ route('backend.contact-messages.show', $row) }}" class="btn btn-sm btn-light border">
                                                    <i class="ti ti-send"></i> ตอบกลับ
                                                </a>

                                                @if ($row->status !== 'done')
                                                    <form method="post" action="{{ route('backend.contact-messages.markDone', $row) }}" class="d-inline js-done-form" data-title="{{ \Illuminate\Support\Str::limit($row->subject, 80) }}">
                                                        @csrf
                                                        @method('patch')
                                                        <button type="submit" class="btn btn-sm btn-light border">
                                                            <i class="ti ti-check"></i> ดำเนินการแล้ว
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">— ไม่มีข้อมูล —</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if (method_exists($rs, 'links'))
                        <div class="mt-3 d-flex justify-content-end">
                            {{ $rs->appends(['q' => $query, 'status' => $status, 'read' => $read])->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
            });

            document.addEventListener('submit', e => {
                const form = e.target;
                if (!form.classList.contains('js-done-form')) return;
                e.preventDefault();

                const title = form.dataset.title || 'รายการนี้';
                Swal.fire({
                    icon: 'question',
                    title: 'ยืนยันดำเนินการเสร็จสิ้น?',
                    html: `คุณต้องการทำเครื่องหมายว่า <b>${title}</b> ดำเนินการแล้วหรือไม่`,
                    showCancelButton: true,
                    confirmButtonText: 'ดำเนินการแล้ว',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true,
                    focusCancel: true
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            });

        })();
    </script>
@endsection
