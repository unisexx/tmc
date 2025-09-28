{{-- resources/views/backend/assessment/create_step1.blade.php --}}
@extends('layouts.main')
@section('title', 'เริ่มรอบประเมิน')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form method="post" action="{{ route('backend.assessment.store') }}" id="formStep1">
                @csrf
                {{-- หน่วยบริการ --}}
                @if (!empty($serviceUnit))
                    <input type="hidden" name="service_unit_id" value="{{ $serviceUnit->id }}">
                    <div class="mb-3">
                        <label class="form-label">หน่วยบริการ</label>
                        <input type="text" class="form-control" value="{{ $serviceUnit->unitName }}" disabled>
                    </div>
                @else
                    <div class="mb-3">
                        <label class="form-label required">เลือกหน่วยบริการ</label>
                        <select name="service_unit_id" class="form-select" required>
                            <option value="">— เลือกหน่วยบริการ —</option>
                            @foreach ($serviceUnits as $u)
                                <option value="{{ $u->id }}">{{ $u->unitName }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">ขั้นที่ 1 : พิจารณาสถานะหน่วยบริการ</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-secondary">
                            ตอบคำถามต่อไปนี้เพื่อประเมินระดับหน่วยบริการของคุณ (อ้างอิงผังในสไลด์)
                        </div>

                        {{-- ตัวอย่าง 3 คำถามหลัก (ปรับเพิ่มได้) --}}
                        <div class="mb-3">
                            <label class="form-label">1) มีแพทย์ประจำ/หมุนเวียนหรือไม่?</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="q1" value="yes"> <label class="form-check-label">มี</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="q1" value="no"> <label class="form-check-label">ไม่มี</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">2) มีบริการฉีดวัคซีนให้แก่ผู้เดินทางหรือไม่?</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="q2" value="yes"> <label class="form-check-label">มี</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="q2" value="no"> <label class="form-check-label">ไม่มี</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">3) มีแพทย์เวชศาสตร์การเดินทาง/TM หรือเทียบเท่า?</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="q3" value="yes"> <label class="form-check-label">มี</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="q3" value="no"> <label class="form-check-label">ไม่มี</label>
                            </div>
                        </div>

                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <label class="form-label">ปีงบประมาณ</label>
                                <select name="fiscalYear" class="form-select">
                                    @foreach ($years as $y)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">รอบการประเมิน</label>
                                <select name="round" class="form-select">
                                    <option value="1">รอบที่ 1 (1 ต.ค. – 31 มี.ค.)</option>
                                    <option value="2">รอบที่ 2 (1 เม.ย. – 30 ก.ย.)</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <div class="p-3 bg-light rounded border">
                                    <div>ผลลัพธ์ระดับ:</div>
                                    <h4 id="levelLabel" class="mb-0"><span class="badge bg-secondary">—</span></h4>
                                    <input type="hidden" name="level" id="levelInput">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary" id="btnNext" disabled>
                            ดำเนินการต่อ <i class="ti ti-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                const radios = document.querySelectorAll('input[name=q1],input[name=q2],input[name=q3]');
                const label = document.getElementById('levelLabel');
                const input = document.getElementById('levelInput');
                const btn = document.getElementById('btnNext');

                function calc() {
                    const q1 = document.querySelector('input[name=q1]:checked')?.value;
                    const q2 = document.querySelector('input[name=q2]:checked')?.value;
                    const q3 = document.querySelector('input[name=q3]:checked')?.value;
                    if (!q1 || !q2 || !q3) {
                        label.innerHTML = '<span class="badge bg-secondary">—</span>';
                        input.value = '';
                        btn.disabled = true;
                        return;
                    }

                    // ตัวอย่างกติกา (จับจากผัง)
                    // มีแพทย์ + มีฉีดวัคซีน + มีแพทย์ TM → สูง,
                    // มีแพทย์ + (มีฉีดวัคซีน หรือ มี TM อย่างใดอย่างหนึ่ง) → กลาง,
                    // อื่น ๆ → พื้นฐาน
                    let level = 'basic';
                    if (q1 === 'yes' && q2 === 'yes' && q3 === 'yes') level = 'advanced';
                    else if (q1 === 'yes' && (q2 === 'yes' || q3 === 'yes')) level = 'medium';

                    input.value = level;
                    const map = {
                        basic: ['พื้นฐาน', 'secondary'],
                        medium: ['กลาง', 'warning'],
                        advanced: ['สูง', 'success']
                    };
                    label.innerHTML = `<span class="badge bg-${map[level][1]}">ระดับ${map[level][0]}</span>`;
                    btn.disabled = false;
                }
                radios.forEach(r => r.addEventListener('change', calc));
            })();
        </script>
    @endpush
@endsection
