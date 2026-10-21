<?php

namespace App\Exports;

use App\Models\JobWorkReceiveItems;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JobWorkReceiveReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;
    protected $modules;

    protected $grandTotalQty = 0;
    protected $grandTotalKgs = 0;
    protected $grandTotalAmount = 0;

    public function __construct($filter_params = null, $user = null, $modules = [])
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;
    }

    public function collection()
    {
        $query = JobWorkReceiveItems::with([
            'job_work_receive',
            'job_work_receive.vendor',
            'job_work_receive.company',
            'product',
        ]);

        // 🔍 Apply filters
        if (!empty($this->filter_params)) {
            $request = (object) $this->filter_params;

            // Search filter
            if (!empty($request->search)) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->whereHas('job_work_receive', function ($qq) use ($search) {
                        $qq->where('job_work_receive_no', 'like', "%{$search}%");
                    })
                        ->orWhereHas('product', function ($p) use ($search) {
                            $p->where('product_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('job_work_receive.vendor', function ($v) use ($search) {
                            $v->where('vendor_name', 'like', "%{$search}%");
                        });
                });
            }

            // Company filter
            if (!empty($request->company)) {
                $query->where('company_id', $request->company);
            } elseif (!empty($this->user?->company_id)) {
                $query->where('company_id', $this->user->company_id);
            }

            // Vendor filter
            if (!empty($request->vendor)) {
                $query->whereHas('job_work_receive', function ($q) use ($request) {
                    $q->where('vendor_id', $request->vendor);
                });
            }

            // Date Range
            if (!empty($request->job_work_receive_date)) {
                $range = explode(" to ", $request->job_work_receive_date);
                if (count($range) == 2) {
                    $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                    $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                    $query->whereHas('job_work_receive', function ($q) use ($from, $to) {
                        $q->whereBetween('job_work_receive_date', [$from, $to]);
                    });
                }
            }
        }
        // dd($this->filter_params);
        $data = $query->latest()->get();

        // Add a dummy footer row (for totals)
        $footer = new \stdClass();
        $footer->is_footer = true;
        $footer->job_work_receive = null;
        $footer->product = null;
        $footer->quantity = $data->sum('quantity');
        $footer->received_weight = $data->sum('received_weight');
        $footer->rate_in_amount = null;
        $footer->amount = null;

        $data->push($footer);

        return $data;
    }

    public function headings(): array
    {
        return [
            'CH. NO',
            'PARTY NAME',
            'ITEM NAME',
            'PROCESS',
            'DATE',
            'RECD. QTY',
            'RECD. KGS',
            'Incoming Scrap',
            'RATE',
            'AMOUNT',
        ];
    }

    public function map($row): array
    {
        // Footer row
        if (isset($row->is_footer) && $row->is_footer) {
            return [
                '',
                '',
                '',
                '',
                'GRAND TOTAL:',
                number_format($row->quantity ?? 0, 3),
                number_format($row->received_weight ?? 0, 3),
                '',
                '',
                '',
            ];
        }

        $jobWork = $row->job_work_receive;
        $vendor = optional($jobWork)->vendor;
        $product = optional($row->product);

        // calculate totals for later styling if needed
        $this->grandTotalQty += $row->quantity ?? 0;
        $this->grandTotalKgs += $row->received_weight ?? 0;

        return [
            optional($jobWork)->job_work_receive_no ?? '-',
            optional($vendor)->vendor_name ?? '-',
            $product->product_name ?? '-',
            optional($jobWork)->process_received->name ?? '-',
            $jobWork && $jobWork->job_work_receive_date
                ? Carbon::parse($jobWork->job_work_receive_date)->format('d/m/Y')
                : '-',
            number_format($row->quantity ?? 0, 3),
            number_format($row->received_weight ?? 0, 3),
            ($row->incoming_scrap > 0) ? number_format($row->incoming_scrap, 3) : '-',
            number_format($row->rate_in_amount ?? 0, 3),
            number_format($row->amount ?? 0, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }
}
