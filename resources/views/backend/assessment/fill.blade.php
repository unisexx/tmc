{{-- resources/views/backend/assessment/fill.blade.php --}}
@extends('layouts.main')
@section('title', 'ทำแบบประเมิน')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="mb-1">ขั้นที่ 2 : ประเมินตนเองทั้ง 6 องค์ประกอบ</h5>
                    <div class="text-muted">
                        ปี {{ $assessment->fiscalYear }} • ระดับ
                        <span class="badge bg-{{ ['basic' => 'secondary', 'medium' => 'warning', 'advanced' => 'success'][$assessment->level] }}">
                            {{ ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'][$assessment->level] }}
                        </span>
                    </div>
                </div>
                <div class="btn-group">
                    <form method="post" action="{{ route('backend.assessment.submit', $assessment) }}">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('ยืนยันส่งแบบประเมิน?');">
                            <i class="ti ti-send"></i> ส่งแบบประเมิน
                        </button>
                    </form>
                </div>
            </div>

            <form method="post" action="{{ route('backend.assessment.fill.store', $assessment) }}" enctype="multipart/form-data">
                @csrf

                @foreach ($components as $comp)
                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h6 class="mb-0">{{ $comp->title }}</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 10%">รหัส</th>
                                            <th>ตัวชี้วัด/คำถาม</th>
                                            <th style="width: 16%">คำตอบ</th>
                                            <th style="width: 26%">หมายเหตุ/ไฟล์แนบ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($comp->items as $item)
                                            @php $ans = $answers[$item->id] ?? null; @endphp
                                            <tr>
                                                <td class="text-muted">{{ $item->code }}</td>
                                                <td>{{ $item->question }}</td>
                                                <td>
                                                    <select class="form-select" name="answers[{{ $item->id }}][value]" required="{{ $item->isRequired ? 'required' : '' }}">
                                                        <option value="">— เลือก —</option>
                                                        @foreach (['yes' => 'มี', 'no' => 'ไม่มี', 'na' => 'ไม่เกี่ยวข้อง'] as $val => $text)
                                                            <option value="{{ $val }}" @selected(($ans->value ?? '') === $val)>{{ $text }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control mb-1" name="answers[{{ $item->id }}][remark]" placeholder="หมายเหตุ (ถ้ามี)" value="{{ $ans->remark ?? '' }}">
                                                    <input type="file" class="form-control" name="answers[{{ $item->id }}][file]">
                                                    @if (!empty($ans?->filePath))
                                                        <small class="text-muted d-block mt-1">
                                                            <i class="ti ti-paperclip"></i>
                                                            <a href="{{ asset('storage/' . $ans->filePath) }}" target="_blank">ดูไฟล์เดิม</a>
                                                        </small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">— ไม่มีข้อสำหรับระดับนี้ —</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="text-end mb-4">
                    <button class="btn btn-outline-primary"><i class="ti ti-device-floppy"></i> บันทึกแบบร่าง</button>
                </div>
            </form>
        </div>
    </div>
@endsection
