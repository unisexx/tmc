{{-- resources/views/backend/st_health_services/form.blade.php --}}
@extends('layouts.main')

@section('title', ($item->exists ? 'แก้ไข' : 'เพิ่ม') . ' การให้บริการ')
@section('breadcrumb-item', 'ตั้งค่า')
@section('breadcrumb-item-active', 'การให้บริการ')

@section('content')
    <div class="row">
        <div class="col-12">

            <x-error-summary :errors="$errors" />

            @php
                $levels = $levels ?? ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'];
                $action = $item->exists ? route('backend.st-health-services.update', $item) : route('backend.st-health-services.store');
                $mode = $item->exists ? 'edit' : 'create';
            @endphp

            <form method="POST" action="{{ $action }}" class="card shadow-sm">
                <div class="card-header py-3">
                    <h5 class="mb-0">
                        {{ $item->exists ? 'แก้ไขบริการ' : 'เพิ่มบริการใหม่' }}
                    </h5>
                </div>

                @csrf
                @if ($item->exists)
                    @method('PUT')
                @endif

                <div class="card-body">
                    <div class="row g-4">

                        {{-- ระดับ --}}
                        <div class="col-12">
                            <label for="level_code" class="form-label">ระดับ <span class="text-danger">*</span></label>
                            <select name="level_code" id="level_code" class="form-select @error('level_code') is-invalid @enderror" required>
                                @foreach ($levels as $k => $v)
                                    <option value="{{ $k }}" {{ old('level_code', $item->level_code) === $k ? 'selected' : '' }}>
                                        {{ $v }}
                                    </option>
                                @endforeach
                            </select>
                            @error('level_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ชื่อบริการ --}}
                        <div class="col-12">
                            <label for="name" class="form-label">ชื่อบริการ <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" placeholder="ระบุชื่อบริการ เช่น ให้คำปรึกษาการเดินทาง" value="{{ old('name', $item->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- สถานะ --}}
                        <div class="col-12">
                            <label for="is_active" class="form-label">สถานะ</label>
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    เปิดการใช้งาน
                                </label>
                            </div>
                        </div>


                    </div>

                    {{-- ปุ่ม --}}
                    <div class="d-flex gap-2 justify-content-end pt-4">
                        <a href="{{ route('backend.st-health-services.index') }}" class="btn btn-light border">
                            <i class="ti ti-arrow-left"></i> ย้อนกลับ
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy"></i>
                            {{ $mode === 'edit' ? 'บันทึกการแก้ไข' : 'บันทึก' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
