{{-- resources/views/backend/self_assessment_component/create.blade.php --}}
@extends('layouts.main')

@section('title', 'แบบประเมินตนเอง 6 องค์ประกอบ')

@section('content')
    <form id="selfAssessForm" method="post" action="{{ route('backend.self-assessment-component.save', ['suLevelId' => $suLevel->id]) }}" enctype="multipart/form-data" data-autosave-url="{{ route('backend.self-assessment-component.autosave', ['suLevelId' => $suLevel->id]) }}">
        @csrf
        <input type="hidden" name="level_code" value="{{ $level->code }}">
        <input type="hidden" name="assess_year" value="{{ $year }}">
        <input type="hidden" name="assess_round" value="{{ $round }}">
        <input type="hidden" name="__action" id="__action" value="save">

        @include('backend.self_assessment_component._form', [
            'mode' => $form ? 'edit' : 'create',
            'form' => $form,
            'level' => $level,
            'components' => $components,
            'sectionsByComp' => $sectionsByComp,
            'questionsBySection' => $questionsBySection,
            'answerMap' => $answerMap ?? collect(),
            'suLevel' => $suLevel,
            'summary' => $summary,
        ])
    </form>
@endsection
