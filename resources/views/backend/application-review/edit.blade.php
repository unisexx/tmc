@extends('layouts.main')

@section('title', 'แก้ไขผู้ใช้งาน')
@section('breadcrumb-item', 'ผู้ใช้งาน')
@section('breadcrumb-item-active', 'แก้ไขผู้ใช้งาน')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>แก้ไขผู้ใช้งาน</h5>
                </div>
                <div class="card-body">
                    <form id="appReviewForm" action="{{ route('backend.application-review.update', $user) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('backend.application-review._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
