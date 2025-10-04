{{-- resources/views/backend/self/edit.blade.php --}}
@extends('layouts.main')
@section('title', 'การประเมินตนเอง')
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">แบบประเมินระดับ {{ $level->name }} ปี {{ $form->assess_year }} รอบ {{ $form->assess_round }}</h5>
            @if ($form->status === 'draft')
                <form class="d-inline" method="post" action="{{ route('backend.self.submit', $form->id) }}">
                    @csrf
                    <button class="btn btn-success">ส่งแบบประเมิน</button>
                </form>
            @endif
        </div>

        <div class="card-body">
            <form method="post" action="{{ route('backend.self.update', $form->id) }}" enctype="multipart/form-data">
                @csrf @method('PUT')
                @include('backend.self._form', [
                    'mode' => 'edit',
                    'form' => $form,
                    'level' => $level,
                    'components' => $components,
                    'sectionsByComp' => $sectionsByComp,
                    'questionsBySection' => $questionsBySection,
                    'answerMap' => $answerMap ?? collect(),
                ])
            </form>
        </div>
    </div>
@endsection
