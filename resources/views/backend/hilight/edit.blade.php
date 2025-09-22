{{-- resources/views/backend/hilight/edit.blade.php --}}
@extends('layouts.main')

@section('title', 'แก้ไขไฮไลท์')
@section('breadcrumb-item', 'เนื้อหา')
@section('breadcrumb-item-active', 'แก้ไขไฮไลท์')

@section('css')
    {{-- CSS เสริมเฉพาะหน้า (ถ้ามี) --}}
@endsection

@section('content')
    <!-- [ Main Content ] start -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-3">
                    <h5 class="mb-0">แก้ไขไฮไลท์</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('backend.hilight.update', $hilight) }}" enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        @include('backend.hilight._form', ['mode' => 'edit'])
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
