@extends('layouts.main')

@section('title', 'แก้ไขข้อมูลหน่วยบริการ')
@section('breadcrumb-item', 'หน่วยบริการ')
@section('breadcrumb-item-active', 'แก้ไขข้อมูลหน่วยบริการ')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>แก้ไขข้อมูลหน่วยบริการ</h5>
                </div>
                <div class="card-body">
                    {{-- resources/views/backend/service_unit/edit.blade.php --}}
                    {{-- ซ่อนปุ่มย้อนกลับ แต่ยังแสดงปุ่มบันทึก --}}
                    <x-service-unit.form :unit="$unit" mode="edit" :action="route('backend.service-unit-profile.update')" method="put" :show-back="false" />
                </div>
            </div>
        </div>
    </div>
@endsection
