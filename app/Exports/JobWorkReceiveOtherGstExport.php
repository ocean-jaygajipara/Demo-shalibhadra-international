<?php

namespace App\Exports;

use App\Helpers\Helper;
use App\Models\JobWorkReceive;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class JobWorkReceiveOtherGstExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
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
        $query = JobWorkReceive::with(['vendor.state', 'job_work_issue', 'process_received', 'job_work_receive_items.product.unit'])
            ->join('job_work_issues', 'job_work_issues.id', '=', 'job_work_receives.job_work_issue_id')
            ->whereColumn('job_work_issues.vendor_id', '!=', 'job_work_receives.vendor_id')
            ->select('job_work_receives.*');


        if (!empty($this->filter_params?->filter_company)) {
            $query->where('job_work_receives.company_id', $this->filter_params->filter_company);
        } elseif ($this->user && $this->user?->company_id) {
            $query->where('job_work_receives.company_id', $this->user->company_id);
        }

        if (!empty($this->filter_params?->filter_vendor)) {
            $query->where('job_work_receives.vendor_id', $this->filter_params->filter_vendor);
        }

        if (!empty($this->filter_params?->filter_job_work_receive_date)) {
            $dates = explode(' to ', $this->filter_params->filter_job_work_receive_date);
            $fromDate = null;
            $toDate = null;

            if (count($dates) == 2) {
                $fromDate = Helper::convert_date($dates[0], "d/m/Y", "Y-m-d");
                $toDate = Helper::convert_date($dates[1], "d/m/Y", "Y-m-d");
            }

            if ($fromDate && $toDate) {
                $query->whereBetween('job_work_receives.job_work_receive_date', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $query->whereDate('job_work_receives.job_work_receive_date', $fromDate);
            }
        }

        if (!empty($this->filter_params?->search)) {
            $search = $this->filter_params->search;
            $query->where(function ($q) use ($search) {
                $q->where('job_work_receives.job_work_receive_no', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q2) use ($search) {
                        $q2->where('vendor_name', 'like', "%{$search}%");
                    });
            });
        }

        // Fetch data
        $jobWorkReceives = $query->latest('job_work_receives.created_at')->get();

        // Flatten data for export (one row per item)
        $exportData = collect();

        foreach ($jobWorkReceives as $receive) {
            foreach ($receive->job_work_receive_items as $item) {
                $exportData->push((object) [
                    'receive' => $receive,
                    'item' => $item
                ]);
            }
        }

        return $exportData;
    }

    public function headings(): array
    {
        $title = $this->modules['title'] ?? 'GST Job Work Receive Other Report';

        return [
            [$title], // Title Row
            [
                'GSTIN of Job Worker(JW)',
                'State (in case of unregistered JW)',
                'Original Challan Number issued by Principal',
                'Original Challan Date issued by Principal (dd-mm-yyyy)',
                'Challan Number issued by Job worker',
                'Challan Date issued by worker (dd-mm-yyyy)',
                'Nature of Job Work done',
                'Description of Goods',
                'Unique Quantity Code (UQC)',
                'Quantity',
                'Losses and wastes Unique Quantity Code (UQC)',
                'Losses and wastes Quantity',
                'Action',
                'Sheet Validation',
                'GST Portal Validation Error(s)'
            ]
        ];
    }

    public function map($row): array
    {
        $receive = $row->receive;
        $item = $row->item;

        $challanDate = optional($receive->job_work_issue)->job_work_issue_date
            ? \Carbon\Carbon::parse($receive->job_work_issue->job_work_issue_date)->format('d-m-Y')
            : '';
        $receiveDate = $receive->job_work_receive_date
            ? \Carbon\Carbon::parse($receive->job_work_receive_date)->format('d-m-Y')
            : '';

        $uqc = optional(optional($item->product)->unit)->name ?? '';

        return [
            optional($receive->vendor)->gst_number ?? '',
            optional(optional($receive->vendor)->state)->state_name ?? '',
            optional($receive->job_work_issue)->job_work_issue_no ?? '',
            $challanDate,
            $receive->job_work_receive_no ?? '',
            $receiveDate,
            optional($receive->process_received)->name ?? '',
            $item->product_name ?? '',
            $uqc,
            $item->received_weight ?? 0,
            $uqc,
            $item->incoming_scrap ?? 0,
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
                // Merge title cell across all columns (A to O)
                $event->sheet->mergeCells('A1:O1');
            },
        ];
    }
}
