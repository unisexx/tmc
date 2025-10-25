@props([
    'level' => null,
    // ถ้าอยากบังคับข้อความเอง ส่ง textOverride เข้ามา เช่น "—"
    'textOverride' => null,
    // ถ้าจะ override สีเองก็ทำได้โดยส่ง colorOverride="#ff00ff"
    'colorOverride' => null,
])

@php
    use Illuminate\Support\Str;

    // โหลดค่าจาก config
    $LEVEL_TEXT = config('tmc.level_text', []);
    $LEVEL_COLORS = config('tmc.level_colors', []);

    // normalize ค่า level (เผื่อส่งมาเป็น null หรือสตริงว่าง)
    $normalizedLevel = $level && trim($level) !== '' ? trim($level) : 'unassessed';

    // ข้อความที่จะแสดง เช่น "ระดับพื้นฐาน" หรือ "ยังไม่ได้ประเมิน"
    $text = $textOverride ?? ($LEVEL_TEXT[$normalizedLevel] ?? '—');

    // สีหลักจาก config หรือ override จาก props
    $color = $colorOverride ?? ($LEVEL_COLORS[$normalizedLevel] ?? '#A8A8A8');
@endphp

<span {{ $attributes->merge(['class' => 'level-badge-map d-inline-flex align-items-center']) }} style="--c: {{ $color }};">
    <span class="dot"></span>
    <span class="name">{{ $text }}</span>
</span>
