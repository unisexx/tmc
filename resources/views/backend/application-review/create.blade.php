@extends('layouts.main')

@section('title', 'เพิ่มผู้ใช้งาน')
@section('breadcrumb-item', 'ผู้ใช้งาน')
@section('breadcrumb-item-active', 'เพิ่มผู้ใช้งาน')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>เพิ่มผู้ใช้งาน</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('backend.application-review.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @include('backend.application-review._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
