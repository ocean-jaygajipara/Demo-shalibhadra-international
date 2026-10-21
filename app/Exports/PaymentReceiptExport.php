<?php

namespace App\Exports;

use App\Models\PaymentReceipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentReceiptExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filter_params;
    protected $srNo = 1;
    protected $user;
    protected $modules;
    protected $loginUserId;

    public function __construct($filter_params = null, $user = null, $modules = [], $loginUserId = null)
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;
        $this->loginUserId = $loginUserId;
    }

    public function collection()
    {
        $query = PaymentReceipt::with(['company', 'customer', 'customerType', 'quotation', 'salesOrder'])
            ->whereNull('deleted_at');

        $filter = $this->filter_params;

        if (!empty($filter->filter_company)) {
            $query->where('company_id', $filter->filter_company);
        } elseif ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user->company_id);
        }

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('receipt_no', 'like', '%' . $filter->search . '%')
                    ->orWhereHas('customer', function ($q2) use ($filter) {
                        $q2->where('customer_name', 'like', '%' . $filter->search . '%');
                    });
            });
        }

        if (!empty($filter->customer_type_id)) {
            $query->where('customer_type_id', $filter->customer_type_id);
        }

        if (!empty($filter->receipt_type)) {
            $query->where('receipt_type', $filter->receipt_type);
        }

        if (!empty($filter->status) && $filter->status !== 'all') {
            $query->where('status', $filter->status);
        }

        if (!empty($filter->date)) {
            $dateRange = explode(' to ', $filter->date);
            try {
                if (count($dateRange) === 2) {
                    $from = Carbon::createFromFormat('d-m-Y', trim($dateRange[0]))->startOfDay();
                    $to = Carbon::createFromFormat('d-m-Y', trim($dateRange[1]))->endOfDay();
                    $query->whereBetween('date', [$from, $to]);
                }
            } catch (\Exception $e) {
                // handle invalid date format silently
            }
        }

        if (!empty($filter->filter_created_by)) {
            $query->where('created_by', $filter->filter_created_by);
        } elseif ($this->user && $this->user->company_id && empty($this->modules['all_data_permission'])) {
            if (!empty($this->modules['personal_data_permission'])) {
                $query->where('created_by', $this->user->id);
            }
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Receipt No',
            'Date',
            'Customer Type',
            'Customer Name',
            'Receipt Type',
            'Quotation No',
            'Sales Order No',
       
            'Payment By',
            'Cheque No',
            'UPI No',
            'Payment Reason',
            'Payment Mode',
            'Amount',
            'Remark',
            'Status',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }

        return $headings;
    }

    public function map($receipt): array
    {
        $row = [
            $this->srNo++,
            $receipt->receipt_no ?? '-',
            $receipt->date ? Carbon::parse($receipt->date)->format('d-m-Y') : '-',
            optional($receipt->customerType)->name ?? '-',
            optional($receipt->customer)->customer_name ?? '-',
            ucfirst($receipt->receipt_type ?? '-'),
            optional($receipt->quotation)->quotation_no ?? '-',
            optional($receipt->salesOrder)->sales_order_no ?? '-',

            $receipt->payment_by ?? '-',
            $receipt->cheque_no ?? '-',
            $receipt->upi_no ?? '-',
            $receipt->payment_reason ?? '-',
            $receipt->payment_mode ?? '-',
            $receipt->amount ?? '0.00',
            $receipt->remark ?? '-',
            ucfirst($receipt->status ?? '-'),
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($row, 1, 0, optional($receipt->company)->company_name ?? '-');
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }
}
