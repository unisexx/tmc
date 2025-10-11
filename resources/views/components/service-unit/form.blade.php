{{-- resources/views/components/service-unit/form.blade.php --}}
@props([
    'action' => '#',
    'method' => 'post',
    'unit',
    'mode' => 'create',
    'showButtons' => true,
    'showBack' => true,
    'backUrl' => null,
    'backLabel' => 'ย้อนกลับ',
    'showSubmit' => true,
])

@php
    $resolvedBackUrl = $backUrl ?? url()->previous();
@endphp

<form method="post" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if (strtolower($method) !== 'post')
        @method($method)
    @endif

    <x-service-unit.fields :unit="$unit" :mode="$mode" />

    @if ($showButtons)
        <div class="d-flex gap-2 justify-content-end">
            @if ($showBack)
                <a href="{{ $resolvedBackUrl }}" class="btn btn-light">
                    <i class="ph-duotone ph-arrow-left"></i> {{ $backLabel }}
                </a>
            @endif
            @if ($showSubmit)
                <button type="submit" class="btn btn-primary">
                    <i class="ph-duotone ph-floppy-disk"></i> บันทึกข้อมูล
                </button>
            @endif
        </div>
    @endif
</form>
