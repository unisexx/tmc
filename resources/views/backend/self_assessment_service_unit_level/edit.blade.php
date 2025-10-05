{{-- resources/views/backend/assessment/step1/edit.blade.php --}}
@extends('layouts.main')

@section('title', 'การประเมินตนเอง')
@section('breadcrumb-item', 'หน่วยบริการ')
@section('breadcrumb-item-active', 'การประเมินตนเอง')

@section('css')
    {{-- ถ้ามี CSS เสริมของหน้า ใส่ได้ที่นี่ --}}
    {{-- <link rel="stylesheet" href="{{ URL::asset('build/css/plugins/style.css') }}"> --}}
@endsection

@section('content')
    <!-- [ Main Content ] start -->
    <form method="post" action="{{ route('backend.self-assessment-service-unit-level.update', $row->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- ส่งตัวแปรให้พาร์เชียล เพื่อ render ค่าจาก $row และปรับปุ่ม/พฤติกรรมเป็นโหมดแก้ไข --}}
        @include('backend.self_assessment_service_unit_level._form', [
            'mode' => 'edit',
            'row' => $row,
        ])
    </form>
    <!-- [ Main Content ] end -->
@endsection

@section('scripts')
    {{-- สคริปต์เฉพาะหน้า (ถ้ามี) --}}
@endsection
