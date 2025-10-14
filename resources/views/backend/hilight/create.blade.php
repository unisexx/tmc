{{-- resources/views/backend/hilight/create.blade.php --}}
@extends('layouts.main')

@section('title', 'เพิ่มไฮไลท์')
@section('breadcrumb-item', 'จัดการข้อมูลหน้าแรก')
@section('breadcrumb-item-active', 'เพิ่มไฮไลท์')

@section('content')
    <div class="row">
        <div class="col-12">

            <x-error-summary :errors="$errors" />

            <div class="card">
                <div class="card-header py-3">
                    <h5 class="mb-0">เพิ่มไฮไลท์</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('backend.hilight.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('backend.hilight._form', ['mode' => 'create'])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
