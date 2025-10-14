{{-- resources/views/backend/faq/edit.blade.php --}}
@extends('layouts.main')

@section('title', 'แก้ไขคำถามที่พบบ่อย')
@section('breadcrumb-item', 'จัดการข้อมูลหน้าแรก')
@section('breadcrumb-item-active', 'แก้ไขคำถามที่พบบ่อย')

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
                    <h5 class="mb-0">แก้ไขคำถามที่พบบ่อย (FAQ)</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('backend.faq.update', $faq) }}">
                        @csrf
                        @method('put')
                        @include('backend.faq._form', ['mode' => 'edit'])
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
