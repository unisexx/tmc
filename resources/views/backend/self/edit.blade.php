{{-- resources/views/backend/self/edit.blade.php --}}
@extends('layouts.main')
@section('title', 'การประเมินตนเอง')
@section('content')
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
@endsection
