<?php

namespace App\Exports;

use App\Models\JobWorkIssueItems;
use App\Helpers\Helper;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JobWorkIssueReportExport implements FromCollection, WithHeadings, WithMapping, WithEvents, ShouldAutoSize, WithStyles
{
    protected $filter_params;
    protected $user;
    protected $modules;
    protected $data = [];

    // Totals
    protected $total_issue_qty = 0;
    protected $total_taxable = 0;
    protected $total_cgst = 0;
    protected $total_sgst = 0;
    protected $total_igst = 0;
    protected $total_gst = 0;
    protected $total_grand = 0;

    public function __construct($filter_params = null, $user = null, $modules = null)
    {
        $this->filter_params = $filter_params;
        $this->user = $user;
        $this->modules = $modules;
    }

    public function collection()
    {
        $query = JobWorkIssueItems::with([
            'job_work_issue',
            'job_work_issue.vendor',
            'job_work_issue.company',
            'product',
        ]);
        if (!empty($this->filter_params)) {
            $request = (object) $this->filter_params;

            if (!empty($request->search)) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->whereHas('job_work_issue', function ($qq) use ($search) {
                        $qq->where('job_work_issue_no', 'like', "%{$search}%");
                    })
                        ->orWhereHas('product', function ($p) use ($search) {
                            $p->where('product_name', 'like', "%{$search}%");
                        });
                });
            }



            if (!empty($request->filter_company)) {
                $query->where('company_id', $request->filter_company);
            } elseif (!empty($this->user?->company_id)) {
                $query->where('company_id', $this->user->company_id);
            }
            // dd($this->filter_params["filter_company"]);
            if (!empty($request->filter_vendor)) {
                $query->whereHas('job_work_issue', function ($q) use ($request) {
                    $q->where('vendor_id', $request->filter_vendor);
                });
            }

          

            if (!empty($request->filter_job_work_issue_date)) {
                $range = explode(" to ", $request->filter_job_work_issue_date);
                if (count($range) == 2) {
                    $from = \Carbon\Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                    $to   = \Carbon\Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                    $query->whereHas('job_work_issue', function ($q) use ($from, $to) {
                        $q->whereBetween('job_work_issue_date', [$from, $to]);
                    });
                }
            }
        }

        $this->data = $query->latest()->get();

        // dd($this->filter_params, $this, Helper::interpolateQuery($query?->toSql(), $query?->getBindings()));

        foreach ($this->data as $row) {
            $this->total_issue_qty += $row->issue_weight ?? 0;
            $this->total_taxable += $row->taxable_amount ?? 0;
            $this->total_cgst += $row->cgst_amount ?? 0;
            $this->total_sgst += $row->sgst_amount ?? 0;
            $this->total_igst += $row->igst_amount ?? 0;
            $this->total_gst += ($row->cgst_amount ?? 0) + ($row->sgst_amount ?? 0) + ($row->igst_amount ?? 0);
            $this->total_grand += ($row->taxable_amount ?? 0) + ($row->cgst_amount ?? 0) + ($row->sgst_amount ?? 0) + ($row->igst_amount ?? 0);
        }

        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Challan No',
            'Date',
            'Party Name',
            'Item Name',
            'Issued Qty',
            'Taxable Amt',
            'CGST',
            'SGST',
            'IGST',
            'Total GST',
            'Grand Total',
        ];
    }

    public function map($row): array
    {
        $cgst = $row->cgst_amount ?? 0;
        $sgst = $row->sgst_amount ?? 0;
        $igst = $row->igst_amount ?? 0;
        $taxable = $row->taxable_amount ?? 0;

        $total_gst = $cgst + $sgst + $igst;
        $grand_total = $taxable + $total_gst;

        return [
            $row->job_work_issue->job_work_issue_no ?? '-',
            $row->job_work_issue && $row->job_work_issue->job_work_issue_date
                ? Carbon::parse($row->job_work_issue->job_work_issue_date)->format('d/m/Y')
                : '-',
            optional($row->job_work_issue->vendor)->vendor_name ?? '-',
            optional($row->product)->product_name ?? '-',
            number_format($row->issue_weight ?? 0, 2),
            number_format($taxable, 2),
            number_format($cgst, 2),
            number_format($sgst, 2),
            number_format($igst, 2),
            number_format($total_gst, 2),
            number_format($grand_total, 2),
        ];
    }

    /**
     * 🟩 Add Grand Total Row
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $rowCount = count($this->data) + 2; // 1 header + data rows

                $sheet->setCellValue("A{$rowCount}", 'Grand Total:');
                $sheet->setCellValue("E{$rowCount}", number_format($this->total_issue_qty, 3));
                $sheet->setCellValue("F{$rowCount}", number_format($this->total_taxable, 2));
                $sheet->setCellValue("G{$rowCount}", number_format($this->total_cgst, 2));
                $sheet->setCellValue("H{$rowCount}", number_format($this->total_sgst, 2));
                $sheet->setCellValue("I{$rowCount}", number_format($this->total_igst, 2));
                $sheet->setCellValue("J{$rowCount}", number_format($this->total_gst, 2));
                $sheet->setCellValue("K{$rowCount}", number_format($this->total_grand, 2));

                // 🖋️ Style the total row
                $sheet->getStyle("A{$rowCount}:K{$rowCount}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                    // 'fill' => [
                    //     'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    //     'color' => ['rgb' => 'E2E2E2'],
                    // ],
                    // 'borders' => [
                    //     'allBorders' => [
                    //         'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    //     ],
                    // ],
                ]);
            },
        ];
    }
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }
}
