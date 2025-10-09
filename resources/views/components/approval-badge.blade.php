@props(['status' => null])

@php
    use Illuminate\Support\Str;

    $mapText = config('assessment.approval_text', []);
    $mapBg = config('assessment.approval_badge_class', []);
    $mapTextC = config('assessment.approval_badge_text_color', []);

    $text = $mapText[$status] ?? '-';
    $bg = $mapBg[$status] ?? 'secondary';
    $tcol = $mapTextC[$status] ?? ($mapTextC['default'] ?? '#212529');

    $textClass = Str::startsWith($tcol, '#') ? null : 'text-' . $tcol;
    $style = Str::startsWith($tcol, '#') ? "color: {$tcol}" : null;

    $classes = trim("badge bg-{$bg} " . ($textClass ?? ''));
@endphp

<span {{ $attributes->merge(['class' => $classes]) }} @if ($style) style="{{ $style }}" @endif>
    {{ $text }}
</span>
