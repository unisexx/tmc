@extends('layouts.main')

@section('title', 'เพิ่มผู้ใช้งาน')
@section('breadcrumb-item', 'ตั้งค่า')
@section('breadcrumb-item-active', 'เพิ่มผู้ใช้งาน')

@section('content')
    <div class="row">
        <div class="col-12">
            <x-error-summary :errors="$errors" />
            <div class="card">
                <div class="card-header">
                    <h5>เพิ่มผู้ใช้งาน</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('backend.user.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @include('backend.user._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
