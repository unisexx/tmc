@props(['level' => null])

@php
    use Illuminate\Support\Str;

    $mapText = config('assessment.level_text', []);
    $mapBg = config('assessment.level_badge_class', []);
    $mapTextC = config('assessment.level_badge_text_color', []);

    $text = $mapText[$level] ?? '—';
    $bg = $mapBg[$level] ?? 'secondary';
    $tcol = $mapTextC[$level] ?? ($mapTextC['default'] ?? '#212529');

    // ถ้าเป็นโค้ดสี #xxxxxx ให้ใช้ style, ถ้าเป็นยูทิลิตี้เช่น pink-900 ให้ใช้ class text-*
    $textClass = Str::startsWith($tcol, '#') ? null : 'text-' . $tcol;
    $style = Str::startsWith($tcol, '#') ? "color: {$tcol}" : null;

    $classes = trim("badge bg-{$bg} " . ($textClass ?? ''));
@endphp

<span {{ $attributes->merge(['class' => $classes]) }} @if ($style) style="{{ $style }}" @endif>
    {{ $text }}
</span>
