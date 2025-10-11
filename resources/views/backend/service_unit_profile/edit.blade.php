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
                    <form id="serviceUnitForm" action="{{ route('backend.service-unit-profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('backend.service_unit_profile._form', ['unit' => $unit])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
