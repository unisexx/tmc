{{-- resources/views/backend/self/create.blade.php --}}
@extends('layouts.main')
@section('title', 'สร้างแบบประเมินตนเอง')
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">สร้างแบบประเมินระดับ {{ $level->name }} ปี {{ $year }} รอบ {{ $round }}</h5>
            <a href="{{ route('backend.self.index') }}" class="btn btn-light">ย้อนกลับ</a>
        </div>

        <div class="card-body">
            <form method="post" action="{{ route('backend.self.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="level_code" value="{{ $level->code }}">
                <input type="hidden" name="assess_year" value="{{ $year }}">
                <input type="hidden" name="assess_round" value="{{ $round }}">
                @include('backend.self._form', [
                    'mode' => 'create',
                    'form' => null,
                    'level' => $level,
                    'components' => $components,
                    'sectionsByComp' => $sectionsByComp,
                    'questionsBySection' => $questionsBySection,
                    'answerMap' => collect(),
                ])
            </form>
        </div>
    </div>
@endsection
