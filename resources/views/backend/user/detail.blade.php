@extends('layouts.main')

@section('title', 'รายละเอียดผู้ใช้งาน')
@section('breadcrumb-item', 'ผู้ใช้งาน')
@section('breadcrumb-item-active', 'รายละเอียด')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">รายละเอียดผู้ใช้งาน</h5>
                    <a href="{{ route('backend.user.downloadPdf', $u) }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-file-download"></i> ดาวน์โหลด PDF
                    </a>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">1. วัตถุประสงค์การลงทะเบียน</h6>
                    <p>{{ $u->reg_purpose ?? '-' }}</p>

                    <h6 class="fw-bold mt-4">2. ข้อมูลทั่วไปของหน่วยบริการ/หน่วยงาน</h6>
                    <ul>
                        <li>ชื่อหน่วยบริการ: {{ $u->org_name }}</li>
                        <li>สังกัด: {{ $u->org_affiliation }}</li>
                        <li>ที่อยู่: {{ $u->org_address }}</li>
                        <li>เบอร์โทร: {{ $u->org_tel }}</li>
                        <li>พิกัด: {{ $u->org_lat }}, {{ $u->org_lng }}</li>
                        <li>วันเวลาทำการ: {!! nl2br(e($u->org_working_hours)) !!}</li>
                    </ul>

                    <h6 class="fw-bold mt-4">3. ข้อมูลผู้ลงทะเบียน</h6>
                    <ul>
                        <li>ชื่อ-สกุล: {{ $u->contact_name }}</li>
                        <li>ตำแหน่ง: {{ $u->contact_position }}</li>
                        <li>โทรศัพท์: {{ $u->contact_mobile }}</li>
                        <li>อีเมล: {{ $u->email }}</li>
                    </ul>

                    <h6 class="fw-bold mt-4">4. การบันทึกข้อมูลล่าสุด</h6>
                    <p>{{ $lastSavedAt?->format('d/m/Y H:i') ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
