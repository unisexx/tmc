{{-- resources/views/backend/self/create.blade.php --}}
@extends('layouts.main')
@section('title', 'สร้างแบบประเมินตนเอง')
@section('content')
    <form method="post" action="{{ route('backend.self-assessment-component.save', ['suLevelId' => $suLevel->id]) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="level_code" value="{{ $level->code }}">
        <input type="hidden" name="assess_year" value="{{ $year }}">
        <input type="hidden" name="assess_round" value="{{ $round }}">
        <input type="hidden" name="__action" id="__action" value="save">

        @include('backend.self._form', [
            'mode' => $form ? 'edit' : 'create',
            'form' => $form,
            'level' => $level,
            'components' => $components,
            'sectionsByComp' => $sectionsByComp,
            'questionsBySection' => $questionsBySection,
            'answerMap' => $answerMap ?? collect(),
        ])
    </form>
@endsection
