{{-- resources/views/backend/self/review.blade.php --}}
@extends('layouts.main')
@section('title', 'ทบทวนแบบประเมิน')
@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">ทบทวนฟอร์ม #{{ $form->id }}</h5>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('backend.self.review', $form->id) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">ข้อเสนอแนะ</label>
                    <textarea class="form-control" name="review_note" rows="4">{{ $form->review_note }}</textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary">บันทึกข้อเสนอแนะ</button>
                    <form method="post" action="{{ route('backend.self.approve', $form->id) }}">@csrf
                        <button class="btn btn-success">อนุมัติ</button>
                    </form>
                    <form method="post" action="{{ route('backend.self.reject', $form->id) }}">@csrf
                        <button class="btn btn-danger">ส่งกลับให้แก้ไข</button>
                    </form>
                </div>
            </form>
        </div>
    </div>
@endsection
