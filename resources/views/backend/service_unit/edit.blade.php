{{-- resources/views/backend/hilight/edit.blade.php --}}
@extends('layouts.main')

@section('title', 'จัดการหน่วยบริการ')
@section('breadcrumb-item', 'ตั้งค่า')
@section('breadcrumb-item-active', 'แก้ไขหน่วยบริการ')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-3">
                    <h5 class="mb-0">แก้ไขหน่วยบริการ</h5>
                </div>
                <div class="card-body">
                    <x-service-unit.form :unit="$unit" mode="edit" :action="route('backend.service-unit.update', $unit)" method="put" :back-url="route('backend.service-unit.index')" />
                </div>
            </div>
        </div>
    </div>
@endsection
