<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DashboardExport implements FromView, WithTitle, WithStyles
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * สร้างเนื้อหา Excel จาก Blade view
     */
    public function view(): View
    {
        return view('backend.dashboard.export_excel', $this->data);
    }

    /**
     * ตั้งชื่อแท็บของชีต
     */
    public function title(): string
    {
        return 'รายงานสรุปหน่วยบริการ';
    }

    /**
     * ปรับสไตล์ของชีตหลัง render
     */
    public function styles(Worksheet $sheet)
    {
        // ทำให้หัวตารางตัวหนา
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        // ปรับขนาดคอลัมน์อัตโนมัติ
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ใส่กรอบเส้นรอบเซลล์ทั้งหมด
        $sheet->getStyle('A1:E' . $sheet->getHighestRow())
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color'       => ['argb' => 'FF999999'],
                    ],
                ],
            ]);
    }
}
