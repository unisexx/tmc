{{-- resources/views/backend/hilight/edit.blade.php --}}
@extends('layouts.main')

@section('title', 'จัดการหน่วยบริการ')
@section('breadcrumb-item', 'หน่วยบริการ')
@section('breadcrumb-item-active', 'แก้ไขหน่วยบริการ')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-3">
                    <h5 class="mb-0">แก้ไขหน่วยบริการ</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('backend.service-unit.update', $unit) }}" enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        @include('backend.service_unit_profile._form', ['mode' => 'edit'])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
