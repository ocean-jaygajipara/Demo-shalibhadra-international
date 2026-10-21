<?php

namespace App\Exports;

use App\Helpers\Helper;
use App\Models\JobWorkIssue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Gate;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class JobWorkIssueGstExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $filter_params;
    protected $user;
    protected $modules;

    public function __construct($filter_params = null, $user = null, $modules = [])
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;
    }

    public function collection()
    {
        $query = JobWorkIssue::with(['vendor.state', 'job_work_type', 'job_work_issue_items.product.unit', 'job_work_issue_items.product.product_category', 'issueForProcess']);

        if (!empty($this->filter_params?->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } elseif ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user->company_id);
        }

        if (!empty($this->filter_params?->filter_vendor)) {
            $query->where('vendor_id', $this->filter_params->filter_vendor);
        }

        if (!empty($this->filter_params?->filter_job_work_type_id)) {
            $query->where('job_work_type_id', $this->filter_params->filter_job_work_type_id);
        }

        if (!empty($this->filter_params?->filter_job_work_issue_date)) {
            $dates = explode(' to ', $this->filter_params->filter_job_work_issue_date);
            $fromDate = null;
            $toDate = null;

            if (count($dates) == 2) {
                $fromDate = Helper::convert_date($dates[0], "d/m/Y", "Y-m-d");
                $toDate = Helper::convert_date($dates[1], "d/m/Y", "Y-m-d");
            }

            if ($fromDate && $toDate) {
                $query->whereBetween('job_work_issue_date', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $query->whereDate('job_work_issue_date', $fromDate);
            }
        }

        if (!empty($this->filter_params?->search)) {
            $search = $this->filter_params->search;
            $query->where(function ($q) use ($search) {
                $q->where('job_work_issue_no', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q2) use ($search) {
                        $q2->where('vendor_name', 'like', "%{$search}%");
                    });
            });
        }

        // Fetch data
        $jobWorkIssues = $query->latest()->get();

        // Flatten data for export (one row per item)
        $exportData = collect();

        foreach ($jobWorkIssues as $issue) {
            foreach ($issue->job_work_issue_items as $item) {
                $exportData->push((object) [
                    'issue' => $issue,
                    'item' => $item
                ]);
            }
        }

        return $exportData;
    }

    public function headings(): array
    {
        $title = $this->modules['title'] ?? 'GST Job Work Issue Report';

        return [
            [$title], // Title Row
            [
                'GSTIN of Job Worker(JW)',
                'State (in case of unregistered JW)',
                'Challan Number',
                'Challan Date (dd-mm-yyyy)',
                'Nature of Job Work done',
                'Description of Goods',
                'Unique Quantity Code (UQC)',
                'Quantity',
                'Taxable Value',
                'Integrated Tax Rate (%)',
                'Integrated Tax Amount',
                'Central Tax Rate (%)',
                'Central Tax Amount',
                'State/UT Tax Rate (%)',
                'State/UT Tax Amount',
                'Cess',
                'Action',
                'Sheet Validation',
                'GST Portal Validation Error(s)'
            ]
        ];
    }

    public function map($row): array
    {
        $issue = $row->issue;
        $item = $row->item;

        $challanDate = $issue->job_work_issue_date
            ? \Carbon\Carbon::parse($issue->job_work_issue_date)->format('d-m-Y')
            : '';

        $uqc = optional(optional($item->product)->unit)->name ?? '';

        return [
            optional($issue->vendor)->gst_number ?? '',
            optional(optional($issue->vendor)->state)->name ?? '',
            $issue->job_work_issue_no ?? '',
            $challanDate,
            optional($issue->issueForProcess)->name ?? '', // Nature of JW
            $item->product_name ?? '',
            $uqc,
            $item->issue_weight ?? 0,
            $item->amount ?? 0,
            '', // IGST Rate
            '', // IGST Amount
            optional($item->gst_percentage) ? $item->gst_percentage / 2 : 0, // CGST Rate
            optional($item->cgst_amount) ? $item->cgst_amount : 0, // CGST Amount
            optional($item->gst_percentage) ? $item->gst_percentage / 2 : 0, // SGST Rate
            optional($item->sgst_amount) ? $item->sgst_amount : 0, // SGST Amount
            '', // Cess
            '', // Action
            '', // Sheet Validation
            '' // GST Portal Validation Error(s)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [ // Title Row
                'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF002060']],
            ],
            2 => [ // Header Row
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF002060']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Merge title cell across all columns (A to S)
                $event->sheet->mergeCells('A1:S1');
            },
        ];
    }
}
