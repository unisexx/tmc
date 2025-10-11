{{-- resources/views/backend/hilight/create.blade.php --}}
@extends('layouts.main')

@section('title', 'จัดการหน่วยบริการ')
@section('breadcrumb-item', 'หน่วยบริการ')
@section('breadcrumb-item-active', 'เพิ่มหน่วยบริการ')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-3">
                    <h5 class="mb-0">เพิ่มหน่วยบริการ</h5>
                </div>
                <div class="card-body">
                    {{-- resources/views/backend/service_unit/create.blade.php --}}
                    <x-service-unit.form :unit="$unit" mode="create" :action="route('backend.service-unit.store')" :backUrl="route('backend.service-unit.index')" method="post" />
                </div>
            </div>
        </div>
    </div>
@endsection
