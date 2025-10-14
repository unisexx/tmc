{{-- resources/views/backend/faq/create.blade.php --}}
@extends('layouts.main')

@section('title', 'เพิ่มคำถามที่พบบ่อย')
@section('breadcrumb-item', 'จัดการข้อมูลหน้าแรก')
@section('breadcrumb-item-active', 'เพิ่มคำถามที่พบบ่อย')

@section('css')
    {{-- ถ้ามี CSS เสริมของหน้า ใส่ได้ที่นี่ --}}
    {{-- <link rel="stylesheet" href="{{ URL::asset('build/css/plugins/style.css') }}"> --}}
@endsection

@section('content')
    <!-- [ Main Content ] start -->
    <div class="row">
        <div class="col-12">
            <x-error-summary :errors="$errors" />
            <div class="card">
                <div class="card-header py-3">
                    <h5 class="mb-0">เพิ่มคำถามที่พบบ่อย (FAQ)</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('backend.faq.store') }}">
                        @csrf
                        @include('backend.faq._form', ['mode' => 'create'])
                        {{-- ปุ่ม submit/back อยู่ในไฟล์ _form.blade.php --}}
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
