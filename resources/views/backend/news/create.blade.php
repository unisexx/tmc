@extends('layouts.main')

@section('title', 'ข่าวประชาสัมพันธ์')
@section('breadcrumb-item', 'จัดการข้อมูลหน้าแรก')
@section('breadcrumb-item-active', 'เพิ่มข่าวประชาสัมพันธ์')

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
                    <h5 class="mb-0">เพิ่มข่าวประชาสัมพันธ์</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('backend.news.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @include('backend.news._form', ['news' => $news, 'mode' => 'create'])
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
@endsection
