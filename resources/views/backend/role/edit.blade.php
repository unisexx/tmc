{{-- resources/views/backend/role/edit.blade.php --}}
@extends('layouts.main')

@section('title', 'แก้ไขสิทธิ์การใช้งาน')
@section('breadcrumb-item', 'ตั้งค่า')
@section('breadcrumb-item-active', 'แก้ไขสิทธิ์การใช้งาน')

@section('css')
    {{-- CSS เสริมเฉพาะหน้า (ถ้ามี) --}}
@endsection

@section('content')
    <!-- [ Main Content ] start -->
    <div class="row">
        <div class="col-12">
            <x-error-summary :errors="$errors" />
            <div class="card">
                <div class="card-header py-3">
                    <h5 class="mb-0">แก้ไขสิทธิ์การใช้งาน</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('backend.role.update', $role->id) }}">
                        @csrf
                        @method('put')
                        @include('backend.role._form', ['mode' => 'edit'])
                        {{-- ปุ่ม submit/back อยู่ใน _form.blade.php --}}
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
@endsection

@section('scripts')
    {{-- JS เฉพาะหน้านี้ (ถ้ามี) --}}
@endsection
