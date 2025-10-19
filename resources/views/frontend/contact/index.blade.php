{{-- resources/views/frontend/contact/index.blade.php --}}
@extends('layouts.frontend')

@section('title', 'ติดต่อเรา')
@section('meta_description', 'ช่องทางการติดต่อ กรมควบคุมโรค สำหรับสอบถามข้อมูลระบบประเมินผลหน่วยบริการสุขภาพผู้เดินทาง')
@section('canonical', route('frontend.contact.index'))
@section('og_title', 'ติดต่อเรา')
@section('og_description', 'ติดต่อสอบถามข้อมูลเพิ่มเติมเกี่ยวกับระบบหน่วยบริการสุขภาพผู้เดินทาง กรมควบคุมโรค')

@section('page_header')
    <header class="bg-light-page border-bottom py-3">
        <div class="container">
            <h1 class="h3 mb-2 section-title text-dark">ติดต่อเรา</h1>
            <nav aria-label="breadcrumb" class="text-end">
                <ol class="breadcrumb mb-0 d-inline-flex justify-content-end">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">หน้าแรก</a></li>
                    <li class="breadcrumb-item active" aria-current="page">ติดต่อเรา</li>
                </ol>
            </nav>
        </div>
    </header>
@endsection

@section('content')
    <section class="my-5">
        <div class="container pt-3 pb-5">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="row g-5">
                {{-- ข้อมูลติดต่อ (ดึงจากหลังบ้าน) --}}
                <div class="col-md-6">
                    <h4 class="mb-3 text-primary">ข้อมูลติดต่อกรมควบคุมโรค</h4>

                    @php
                        $addr = trim((string) ($contact->address ?? ''));
                        $tel = trim((string) ($contact->tel ?? ''));
                        $fax = trim((string) ($contact->fax ?? ''));
                        $mail = trim((string) ($contact->email ?? ''));
                        $fb = trim((string) ($contact->facebook ?? ''));
                        $yt = trim((string) ($contact->youtube ?? ''));
                        $map = trim((string) ($contact->map ?? ''));
                    @endphp

                    @if ($addr)
                        <p class="mb-2 fw-semibold">กรมควบคุมโรค</p>
                        <p class="text-muted mb-3">{!! nl2br(e($addr)) !!}</p>
                    @endif

                    @if ($tel)
                        <p class="mb-1"><i class="bi bi-telephone-fill me-2 text-primary"></i>โทรศัพท์: {{ $tel }}</p>
                    @endif
                    @if ($fax)
                        <p class="mb-1"><i class="bi bi-printer-fill me-2 text-primary"></i>แฟกซ์: {{ $fax }}</p>
                    @endif
                    @if ($mail)
                        <p class="mb-1"><i class="bi bi-envelope-fill me-2 text-primary"></i>อีเมล: <a href="mailto:{{ $mail }}">{{ $mail }}</a></p>
                    @endif

                    <div class="d-flex flex-wrap gap-2 my-3">
                        @if ($fb)
                            <a class="btn btn-outline-primary btn-sm" href="{{ $fb }}" target="_blank" rel="noopener">
                                <i class="bi bi-facebook me-1"></i> Facebook
                            </a>
                        @endif
                        @if ($yt)
                            <a class="btn btn-outline-danger btn-sm" href="{{ $yt }}" target="_blank" rel="noopener">
                                <i class="bi bi-youtube me-1"></i> YouTube
                            </a>
                        @endif
                    </div>

                    <hr>

                    <div class="rounded shadow-sm overflow-hidden" style="height:350px;">
                        @if ($map)
                            @if (\Illuminate\Support\Str::contains(strtolower($map), 'iframe'))
                                <div class="ratio ratio-16x9">{!! $map !!}</div>
                            @else
                                {{-- ถ้าเป็นลิงก์ ให้ปุ่มเปิดแผนที่ --}}
                                <a href="{{ $map }}" class="btn btn-outline-secondary w-100 h-100 d-flex align-items-center justify-content-center" target="_blank" rel="noopener">
                                    <i class="bi bi-map me-2"></i> เปิดแผนที่
                                </a>
                            @endif
                        @else
                            {{-- fallback แผนที่มาตรฐาน --}}
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3873.7600415279862!2d100.52238106727599!3d13.853437336157715!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30e29b5fc50be2fd%3A0xfcbdc03279eb9bdf!2z4LiB4Lij4Lih4LiE4Lin4Lia4LiE4Li44Lih4LmC4Lij4LiE!5e0!3m2!1sth!2sth!4v1759646646199!5m2!1sth!2sth" style="border:0;height:100%;width:100%;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="แผนที่กรมควบคุมโรค"></iframe>
                        @endif
                    </div>
                </div>

                {{-- ฟอร์มติดต่อ --}}
                <div class="col-md-6">
                    {{-- resources/views/frontend/contact/index.blade.php (เฉพาะฟอร์ม) --}}
                    <h4 class="mb-4 text-primary">ส่งข้อความถึงเรา</h4>

                    <form method="POST" action="{{ route('frontend.contact.send') }}" class="row g-3 needs-validation" novalidate>
                        @csrf

                        {{-- Honeypot --}}
                        <div class="visually-hidden" aria-hidden="true">
                            <label for="hp">Leave blank</label>
                            <input type="text" name="hp" id="hp" value="">
                        </div>

                        <div class="col-12">
                            <label for="name" class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control form-control-lg" required value="{{ old('name') }}">
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control form-control-lg" required value="{{ old('email') }}">
                            @error('email')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">โทรศัพท์</label>
                            <input type="tel" name="phone" id="phone" class="form-control form-control-lg" value="{{ old('phone') }}">
                            @error('phone')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="subject" class="form-label">หัวข้อ <span class="text-danger">*</span></label>
                            <input type="text" name="subject" id="subject" class="form-control form-control-lg" required value="{{ old('subject') }}">
                            @error('subject')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="message" class="form-label">ข้อความ <span class="text-danger">*</span></label>
                            <textarea name="message" id="message" rows="4" class="form-control form-control-lg" required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Math CAPTCHA --}}
                        <div class="col-md-6">
                            <label for="captcha" class="form-label">ยืนยันว่าเป็นมนุษย์: {{ $captchaQuestion ?? '' }} <span class="text-danger">*</span></label>
                            <input type="number" name="captcha" id="captcha" class="form-control form-control-lg" required>
                            @error('captcha')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                            <div class="form-text">ถ้าตอบผิดหรือหมดอายุ ระบบจะสร้างคำถามใหม่ให้</div>
                        </div>

                        <div class="col-12 d-flex gap-2 mt-2">
                            <button class="btn btn-primary btn-lg" type="submit">
                                <i class="bi bi-send me-2"></i>ส่งข้อความ
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </section>
@endsection
