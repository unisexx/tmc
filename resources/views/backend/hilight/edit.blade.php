{{-- resources/views/backend/hilight/edit.blade.php --}}
@extends('layouts.main')

@section('title', 'แก้ไขไฮไลท์')
@section('breadcrumb-item', 'จัดการข้อมูลหน้าแรก')
@section('breadcrumb-item-active', 'แก้ไขไฮไลท์')

@section('content')
    <div class="row">
        <div class="col-12">

            <x-error-summary :errors="$errors" />

            <div class="card">
                <div class="card-header py-3">
                    <h5 class="mb-0">แก้ไขไฮไลท์</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('backend.hilight.update', $hilight) }}" enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        @include('backend.hilight._form', ['mode' => 'edit'])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
