@props([
    'level' => null,
    // ถ้าอยากบังคับข้อความเอง ส่ง textOverride เข้ามา เช่น "—"
    'textOverride' => null,
    // ถ้าจะ override สีเองก็ทำได้โดยส่ง colorOverride="#ff00ff"
    'colorOverride' => null,
])

@php
    use Illuminate\Support\Str;

    // ข้อความของระดับ
    $LEVEL_TEXT = array_merge(
        [
            'basic' => 'ระดับพื้นฐาน',
            'medium' => 'ระดับกลาง',
            'advanced' => 'ระดับสูง',
            'unassessed' => 'ยังไม่ได้ประเมิน',
        ],
        (array) config('tmc.level_text', []),
    );

    // สีหลักของแต่ละระดับ (ใช้บนแผนที่)
    // fallback ไว้ให้ ถ้า config('tmc.level_colors') ไม่มี key นั้น
    $LEVEL_COLORS = array_replace(
        [
            'basic' => '#FF4560', // ชมพู
            'medium' => '#FEB019', // ส้ม
            'advanced' => '#00E396', // เขียว
            'unassessed' => '#A8A8A8', // เทา
        ],
        (array) config('tmc.level_colors'),
    );

    $normalizedLevel = $level ?: 'unassessed';

    // เลือกข้อความ
    $text = $textOverride ?? ($LEVEL_TEXT[$normalizedLevel] ?? '—');

    // เลือกสีหลัก (var(--c))
    $color = $colorOverride ?? ($LEVEL_COLORS[$normalizedLevel] ?? '#A8A8A8');

    // output structure:
    // <span class="level-badge-map" style="--c: {color}">
    //    <span class="dot"></span>
    //    <span class="name">{text}</span>
    // </span>

@endphp

<span {{ $attributes->merge(['class' => 'level-badge-map d-inline-flex align-items-center']) }} style="--c: {{ $color }};">
    <span class="dot"></span>
    <span class="name">{{ $text }}</span>
</span>
