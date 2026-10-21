<?php

namespace App\Exports;

use App\Helpers\Helper;
use App\Models\WithdrawRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WithdrawalRequestExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $srNo = 1;
    protected $filters;
    protected $user;

    public function __construct($filters = [], $user = null)
    {
        $this->filters = (object) $filters;
        $this->user = $user;
    }

    public function collection()
    {
        $query = WithdrawRequest::with(['company', 'customer']);

        if (!empty($this->filters->search)) {
            $query->whereHas('customer', function ($q) {
                $q->where('customer_name', 'like', '%' . $this->filters->search . '%');
            });
        }

        if (!empty($this->filters->filter_status) && $this->filters->filter_status !== 'all') {
            $query->where('status', $this->filters->filter_status);
        }

        if (!empty($this->filters->filter_company)) {
            $query->where('company_id', $this->filters->filter_company);
        }

        if (!empty($this->user?->company_id)) {
            $query->where('company_id', $this->user->company_id);
        }

        return $query->latest()->get();
    }

    public function map($item): array
    {
        $row = [
            $this->srNo++,
            optional($item->customer)->customer_name ?? '-',
            $item->amount ?? '-',
            ucfirst($item->status) ?? '-',
            $item->withdraw_request_date ? \Carbon\Carbon::parse($item->withdraw_request_date)->format('d/m/Y') : '-',
            // $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i A') : '-',
        ];

        if (!$this->user || empty($this->user->company_id)) {
            array_splice($row, 1, 0, optional($item->company)->company_name ?? '-');
        }

        return $row;
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Customer Name',
            'Amount',
            'Status',
            'Withdraw Date',
            // 'Created At',
        ];

        if (!$this->user || empty($this->user->company_id)) {
            array_splice($headings, 1, 0, 'Company Name');
        }

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }

    public function title(): string
    {
        return 'Withdraw Requests';
    }
}
