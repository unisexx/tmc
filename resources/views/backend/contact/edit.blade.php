@extends('layouts.main')

@section('title', 'ข้อมูลติดต่อ')
@section('breadcrumb-item', 'เนื้อหา')
@section('breadcrumb-item-active', 'ข้อมูลติดต่อ')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">ข้อมูลติดต่อ</h5>
                    {{-- <span class="text-muted small">บันทึกจะมีเพียง 1 รายการ (id = 1)</span> --}}
                </div>

                <div class="card-body">
                    <form action="{{ route('backend.contact.update') }}" method="POST" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row g-4">
                            {{-- ที่อยู่ --}}
                            <div class="col-12">
                                <label for="addressInput" class="form-label">ที่อยู่</label>
                                <textarea id="addressInput" name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address', $contact->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="col-md-6">
                                <label for="emailInput" class="form-label">อีเมล</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-mail"></i></span>
                                    <input type="email" id="emailInput" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $contact->email) }}" placeholder="name@example.com">
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- โทรศัพท์ --}}
                            <div class="col-md-3">
                                <label for="telInput" class="form-label">โทรศัพท์</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-phone"></i></span>
                                    <input type="text" id="telInput" name="tel" class="form-control @error('tel') is-invalid @enderror" value="{{ old('tel', $contact->tel) }}" placeholder="0X-XXX-XXXX">
                                    @error('tel')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- แฟกซ์ --}}
                            <div class="col-md-3">
                                <label for="faxInput" class="form-label">แฟกซ์</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-printer"></i></span>
                                    <input type="text" id="faxInput" name="fax" class="form-control @error('fax') is-invalid @enderror" value="{{ old('fax', $contact->fax) }}" placeholder="แฟกซ์ (ถ้ามี)">
                                    @error('fax')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- ลิงก์ Google Map หรือโค้ด Iframe --}}
                            <div class="col-12">
                                <label for="mapInput" class="form-label">แผนที่ (ลิงก์ Google Maps หรือโค้ด Iframe)</label>
                                <textarea id="mapInput" name="map" class="form-control @error('map') is-invalid @enderror" rows="3" placeholder="วางลิงก์ Google Maps หรือโค้ด iframe">{{ old('map', $contact->map) }}</textarea>
                                @error('map')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                {{-- Preview แผนที่แบบง่าย: ถ้ามีคำว่า iframe จะแสดงทันที --}}
                                @php
                                    $mapVal = old('map', $contact->map);
                                @endphp
                                @if ($mapVal)
                                    <div class="mt-3">
                                        @if (Str::contains(strtolower($mapVal), 'iframe'))
                                            <div class="ratio ratio-16x9">{!! $mapVal !!}</div>
                                        @else
                                            <a href="{{ $mapVal }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">
                                                <i class="ti ti-map-pin"></i> เปิดแผนที่
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Facebook --}}
                            <div class="col-md-6">
                                <label for="facebookInput" class="form-label">Facebook (URL)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-brand-facebook"></i></span>
                                    <input type="url" id="facebookInput" name="facebook" class="form-control @error('facebook') is-invalid @enderror" value="{{ old('facebook', $contact->facebook) }}" placeholder="https://facebook.com/yourpage">
                                    @error('facebook')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- YouTube --}}
                            <div class="col-md-6">
                                <label for="youtubeInput" class="form-label">YouTube (URL)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-brand-youtube"></i></span>
                                    <input type="url" id="youtubeInput" name="youtube" class="form-control @error('youtube') is-invalid @enderror" value="{{ old('youtube', $contact->youtube) }}" placeholder="https://youtube.com/@yourchannel">
                                    @error('youtube')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- ปุ่ม --}}
                            <div class="col-12 d-flex gap-2 justify-content-end pt-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy"></i> บันทึก
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection
