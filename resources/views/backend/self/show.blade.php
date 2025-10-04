{{-- resources/views/backend/self/show.blade.php --}}
@extends('layouts.main')
@section('title', 'สรุปผลการประเมินตนเอง')
@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">สรุปผล — ระดับ {{ strtoupper($form->level_code) }} / สถานะ: {{ $form->status }}</h5>
        </div>
        <div class="card-body">
            @foreach ($byComp as $no => $set)
                <h6 class="mt-3">{{ $no }}. {{ \App\Models\AssessmentComponent::where('no', $no)->value('name') }}</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <strong>คุณสมบัติ/ศักยภาพที่มี</strong>
                            <ul class="mb-0">
                                @forelse($set['have'] as $txt)
                                <li>{{ $txt }}</li> @empty <li>-</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-warning">
                            <strong>ช่องว่างการพัฒนา (GAP)</strong>
                            <ul class="mb-0">
                                @forelse($set['gap'] as $txt)
                                <li>{{ $txt }}</li> @empty <li>-</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach

            <h6 class="mt-3">ข้อเสนอ/แผนพัฒนา</h6>
            <ul>
                @forelse($form->suggestions as $sg)
                    <li>{{ $sg->text }}
                        @if ($sg->attachment_path)
                            — <a target="_blank" href="{{ Storage::disk('public')->url($sg->attachment_path) }}">ไฟล์แนบ</a>
                        @endif
                    </li>
                @empty <li>-</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
