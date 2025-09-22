{{-- resources/views/backend/hilight/create.blade.php --}}
@extends('layouts.main')

@section('title', 'เพิ่มไฮไลท์')
@section('breadcrumb-item', 'เนื้อหา')
@section('breadcrumb-item-active', 'เพิ่มไฮไลท์')

@section('css')
    {{-- ถ้ามี CSS เสริมของหน้า ใส่ได้ที่นี่ --}}
    {{-- <link rel="stylesheet" href="{{ URL::asset('build/css/plugins/style.css') }}"> --}}
@endsection

@section('content')
    <!-- [ Main Content ] start -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-3">
                    <h5 class="mb-0">เพิ่มไฮไลท์</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('backend.hilight.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('backend.hilight._form', ['mode' => 'create'])
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
