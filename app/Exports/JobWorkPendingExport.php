<?php

namespace App\Exports;

use App\Models\JobWorkIssueItems;
use App\Models\JobWorkReceiveItems;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class JobWorkPendingExport implements FromArray, ShouldAutoSize, WithStyles, WithEvents
{
    protected $filter_params;
    protected $user;
    protected $modules;
    protected $headerRows = [];

    public function __construct($filter_params = null, $user = null, $modules = [])
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;
    }

    public function array(): array
    {
        $modules = $this->modules;

        $issueItemsQuery = JobWorkIssueItems::with([
            'job_work_issue.vendor',
            'product',
            'job_work_receive_items.job_work_receive'
        ])->whereHas('job_work_issue', function ($q) use ($modules) {
            if (!empty($modules['company_id'])) {
                $q->where('company_id', $modules['company_id']);
            }

            if ($this->filter_params->filter_vendor) {
                $q->where('vendor_id', $this->filter_params->filter_vendor);
            }

            if ($this->filter_params->filter_job_work_issue_date) {
                $range = explode(" to ", $this->filter_params->filter_job_work_issue_date);
                if (count($range) === 2) {
                    $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                    $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                    $q->whereBetween('job_work_issue_date', [$from, $to]);
                }
            }

            if ($this->filter_params->filter_report_status) {
                if($this->filter_params->filter_report_status == 'pending_report'){
                    $q->whereRaw('COALESCE(master_total_pending, 0) > 0');
                }else if($this->filter_params->filter_report_status == 'completed_report'){
                    $q->whereRaw('COALESCE(master_total_pending, 0) = 0');
                }
            }
        });


        $issueItems = $issueItemsQuery->get();
        $grouped = $issueItems->groupBy('job_work_issue_id');

        // === Excel Table Data ===
        $data = [];
        $data[] = [
            'Job Work Pending Report - Printed on: ' . now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A')
        ];

        $rowCounter = 1;

        foreach ($grouped as $items) {
            $challan = $items->first()->job_work_issue;
            $vendor = $challan?->vendor?->vendor_name ?? '-';
            $issueDate = $challan?->job_work_issue_date
                ? Carbon::parse($challan->job_work_issue_date)->format('d-m-Y')
                : '-';

            $jobWorkIssueIds = $challan->id;
            $receiveItems = JobWorkReceiveItems::with('job_work_receive')
                ->whereHas('job_work_receive', function ($q) use ($jobWorkIssueIds) {
                    $q->where('job_work_issue_id', $jobWorkIssueIds);
                })
                ->get();
            $master_total_pending = $challan?->master_total_pending ?? '-';

            // === Header (gray row)
            $data[] = ['Challan', 'Date', 'Party Name', 'Item Name', 'Weight'];
            $rowCounter++;
            $this->headerRows[] = ['row' => $rowCounter, 'cols' => count($data[count($data) - 1])];


            // === Issue rows
            foreach ($items as $itm) {

                $issueWeight = (float) ($itm->issue_weight ?? 0);
                $blPercentage = (float) ($itm->bl_percentage ?? 0);

                $finalWeight = $issueWeight + ($issueWeight * $blPercentage / 100);

                $data[] = [
                    $challan->job_work_issue_no ?? '-',
                    $issueDate,
                    $vendor,
                    $itm->product?->product_name ?? '-',
                    number_format($finalWeight ?? 0, 3)
                ];
                $rowCounter++;
            }

            // === Sub Header (gray row)
            $data[] = ['Chl No.', 'Date', 'Item Name (Code)', 'KG', 'Qty', 'Wire', 'Scrap', 'BL', 'Total'];
            $rowCounter++;
            $this->headerRows[] = ['row' => $rowCounter, 'cols' => count($data[count($data) - 1])];


            // === Receive rows
            if ($receiveItems->count()) {

                $sumKg = 0;
                $sumQty = 0;
                $sumScrap = 0;
                $sumBL = 0;
                $sumTotal = 0;

                foreach ($receiveItems as $rec) {
                    $receiveDate = $rec->job_work_receive?->job_work_receive_date
                        ? Carbon::parse($rec->job_work_receive->job_work_receive_date)->format('d-m-Y')
                        : '-';
                    $mergedItem = trim(($rec->product_name ?? '-') .
                        (isset($rec->product_code) && $rec->product_code ? ' (' . $rec->product_code . ')' : ''));

                    $kg     = (float) ($rec->received_weight ?? 0);
                    $qty    = (float) ($rec->quantity ?? 0);
                    $scrap  = (float) ($rec->incoming_scrap ?? 0);
                    $bl     = (float) ($rec->bl_percentage ?? 0);
                    $total  = (float) ($rec->received_weight ?? 0);

                    $sumKg    += $kg;
                    $sumQty   += $qty;
                    $sumScrap += $scrap;
                    $sumBL    += $bl;
                    $sumTotal += $total;

                    $data[] = [
                        $challan->job_work_issue_no ?? '-',
                        $receiveDate,
                        $mergedItem,
                        number_format($rec->received_weight ?? 0, 3),
                        number_format($rec->quantity ?? 0, 3),
                        '',
                        number_format($rec->incoming_scrap ?? 0, 3),
                        number_format($rec->bl_percentage ?? 0, 3),
                        number_format($rec->received_weight ?? 0, 3),
                    ];

                    $data[] = [
                        '',
                        '',
                        'TOTAL',
                        number_format($sumKg, 3),
                        number_format($sumQty, 3),
                        '',
                        number_format($sumScrap, 3),
                        number_format($sumBL, 3),
                        number_format($sumTotal, 3),
                    ];
                    $rowCounter++;
                }
            } else {
                $data[] = ['-', '-', 'No Receive Entries', '', '', '', '', ''];
                $rowCounter++;
            }

            // === Pending weight
            $data[] = ['', '', '', '', 'Challan Pending Wt.: ' . number_format($master_total_pending, 3)];
            $rowCounter++;
            $data[] = [];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
        return [];
    }

    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();

            // Define gray fill
            $grayFill = [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'D9D9D9'], // light gray
            ];

            foreach ($this->headerRows as $header) {
                $row = $header['row'];
                $colCount = $header['cols'];

                if ($colCount <= 0) continue;

                // Convert column count to Excel column letter (e.g., 5 → E)
                $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
                $range = "A{$row}:{$highestCol}{$row}";

                // Apply background fill
                $sheet->getStyle($range)->getFill()->applyFromArray($grayFill);

                // Bold + Centered
                $sheet->getStyle($range)->getFont()->setBold(true);
                $sheet->getStyle($range)->getAlignment()
                    ->setHorizontal('center')
                    ->setVertical('center');

                // Add borders
                $sheet->getStyle($range)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }

            // Optional: make "Job Work Pending Report" (A1) merged and centered
            $sheet->mergeCells('A1:E1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

            // Italic for printed time
            $sheet->getStyle('A2')->getFont()->setItalic(true);
        },
    ];
}



}
