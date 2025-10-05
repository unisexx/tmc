<?php

namespace App\View\Components;

use Illuminate\View\Component;

class LevelBadge extends Component
{
    public ?string $level;

    public function __construct(?string $level = null)
    {
        $this->level = $level;
    }

    public function render()
    {
        // ดึง mapping จาก config
        $texts  = config('assessment.level_text');
        $bgMap  = config('assessment.level_badge_class');
        $txtMap = config('assessment.level_badge_text_color');

        $lv    = $this->level;
        $bg    = $lv ? ($bgMap[$lv] ?? 'secondary') : 'secondary';
        $txt   = $lv ? ($txtMap[$lv] ?? 'white') : 'white';
        $label = $lv ? ($texts[$lv] ?? '—') : '—';

        // ส่งค่าต่อไปยัง blade component
        return view('components.level-badge', compact('bg', 'txt', 'label'));
    }
}
