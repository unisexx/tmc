{{-- resources/views/backend/role/create.blade.php --}}
@extends('layouts.main')

@section('title', 'เพิ่มสิทธิ์การใช้งาน')
@section('breadcrumb-item', 'ตั้งค่า')
@section('breadcrumb-item-active', 'เพิ่มสิทธิ์การใช้งาน')

@section('css')
    {{-- ถ้ามี CSS เสริมของหน้า ใส่ได้ที่นี่ --}}
@endsection

@section('content')
    <!-- [ Main Content ] start -->
    <div class="row">
        <div class="col-12">
            <x-error-summary :errors="$errors" />
            <div class="card">
                <div class="card-header py-3">
                    <h5 class="mb-0">เพิ่มสิทธิ์การใช้งาน</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('backend.role.store') }}">
                        @csrf
                        @include('backend.role._form', ['mode' => 'create'])
                        {{-- ปุ่ม submit/back อยู่ในไฟล์ _form.blade.php ตามที่ต้องการ --}}
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
@endsection

@section('scripts')
    {{-- สคริปต์เฉพาะหน้า (ถ้ามี) --}}
@endsection
