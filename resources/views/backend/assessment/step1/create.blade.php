{{-- resources/views/backend/assessment/step1/create.blade.php --}}
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
    <form method="post" action="{{ route('backend.assessment.step1.store') }}" enctype="multipart/form-data">
        @csrf
        @include('backend.assessment.step1._form', ['mode' => 'create'])
    </form>
    <!-- [ Main Content ] end -->
@endsection

@section('scripts')
    {{-- สคริปต์เฉพาะหน้า (ถ้ามี) --}}
@endsection
