<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DashboardExport implements FromView, WithTitle, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // ใช้ blade excel.blade.php ที่เราแก้ล่าสุด (หัวข้อแต่ละส่วนอยู่ใน <table><td colspan="6">)
    public function view(): View
    {
        return view('backend.dashboard.export_overview.excel', $this->data);
    }

    public function title(): string
    {
        $yearTh = ($this->data['filterYear'] ?? now()->year) + 543;
        $round  = $this->data['filterRound'] ?? '-';

        return "สรุปภาพรวม {$yearTh} รอบ {$round}";
    }

    public function styles(Worksheet $sheet)
    {
        // autosize บลาๆ
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ใส่เส้นขอบรอบข้อมูลทั้งหมดแบบบาง ๆ
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$highestCol}{$highestRow}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FFCCCCCC'],
                    ],
                ],
            ]);

        // ไม่ต้อง mergeCells() ที่นี่อีก
        return [];
    }
}
