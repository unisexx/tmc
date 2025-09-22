{{-- resources/views/backend/faq/_form.blade.php --}}
@php
    // ใช้ $faq จากหน้า create/edit
@endphp

<div class="row g-4">
    {{-- คำถาม --}}
    <div class="col-12">
        <label for="questionInput" class="form-label">คำถาม <span class="text-danger">*</span></label>
        <input type="text" name="question" id="questionInput" class="form-control @error('question') is-invalid @enderror" value="{{ old('question', $faq->question) }}" required>
        @error('question')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- คำตอบ --}}
    <div class="col-12">
        <label for="answerInput" class="form-label">คำตอบ <span class="text-danger">*</span></label>
        <textarea name="answer" id="answerInput" rows="6" class="form-control @error('answer') is-invalid @enderror" required>{{ old('answer', $faq->answer) }}</textarea>
        @error('answer')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">รองรับข้อความยาวได้ตามต้องการ</small>
    </div>

    {{-- ลำดับแสดงผล --}}
    {{-- <div class="col-md-3">
        <label for="orderingInput" class="form-label">ลำดับแสดงผล</label>
        <input type="number" name="ordering" id="orderingInput" class="form-control @error('ordering') is-invalid @enderror" min="0" value="{{ old('ordering', $faq->ordering ?? 0) }}">
        @error('ordering')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div> --}}

    {{-- สถานะ --}}
    <div class="col-md-3">
        <label for="isActiveSelect" class="form-label">สถานะ</label>
        <select name="is_active" id="isActiveSelect" class="form-select @error('is_active') is-invalid @enderror">
            <option value="1" {{ old('is_active', $faq->is_active ?? true) ? 'selected' : '' }}>เปิดใช้งาน</option>
            <option value="0" {{ old('is_active', $faq->is_active ?? true) ? '' : 'selected' }}>ปิดใช้งาน</option>
        </select>
        @error('is_active')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- ปุ่ม --}}
    <div class="col-12 d-flex gap-2 justify-content-end pt-2">
        <a href="{{ route('backend.faq.index') }}" class="btn btn-light">
            <i class="ti ti-arrow-left"></i> ย้อนกลับ
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy"></i>
            {{ ($mode ?? 'create') === 'edit' ? 'บันทึกการแก้ไข' : 'บันทึก' }}
        </button>
    </div>
</div>
