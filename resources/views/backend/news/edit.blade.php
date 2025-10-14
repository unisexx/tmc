@extends('layouts.main')

@section('title', 'ข่าวประชาสัมพันธ์')
@section('breadcrumb-item', 'จัดการข้อมูลหน้าแรก')
@section('breadcrumb-item-active', 'แก้ไขข่าวประชาสัมพันธ์')

@section('css')
    <link rel="stylesheet" href="{{ URL::asset('build/css/plugins/style.css') }}">
@endsection

@section('content')
    <!-- [ Main Content ] start -->
    <div class="row">
        <div class="col-12">
            <x-error-summary :errors="$errors" />
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0">แก้ไขข่าวประชาสัมพันธ์</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('backend.news.update', $news) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        @include('backend.news._form', ['news' => $news, 'mode' => 'edit'])
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
@endsection
