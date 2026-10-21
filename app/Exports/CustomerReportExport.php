<?php

namespace App\Exports;

use App\Models\SalesOrder;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Dispatch;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Gate;

class CustomerReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $filter_params;
    protected $user;
    protected $rows = [];

    public function __construct($filter_params = null, $user = null)
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->prepareData();
    }

    protected function prepareData()
    {
        $fromDate = null;
        $toDate = null;

        if (!empty($this->filter_params->filter_date)) {
            $filter_date = explode(" to ", $this->filter_params->filter_date);
            if (count($filter_date) === 2) {
                $fromDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($filter_date[0]))->format('Y-m-d');
                $toDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($filter_date[1]))->format('Y-m-d');
            }
        }

        if (!$fromDate || !$toDate) {
            return;
        }

        $customersQuery = Customer::with(['company', 'customerType', 'state', 'city']);
        $customersQuery = $customersQuery->orderBy('id', 'desc');

        if (Auth::guard('team_person')->check()) {
            $customersQuery->where('company_id', $this->user->company_id);
        }

        if (!empty($this->filter_params->filter_company)) {
            $customersQuery->where('company_id', $this->filter_params->filter_company);
        }

        if (!empty($this->filter_params->filter_customer)) {
            $customersQuery->where('id', $this->filter_params->filter_customer);
        }

        if (!empty($this->filter_params->filter_customer_type)) {
            $customersQuery->where('customer_type_id', $this->filter_params->filter_customer_type);
        }

        if (!empty($this->filter_params->search)) {
            $search = $this->filter_params->search;
            $customersQuery->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhereHas('state', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('city', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $this->rows = $customersQuery->get();
    }

   public function collection()
    {
        $finalRows = collect();

        $fromDate = null;
        $toDate = null;

        if (!empty($this->filter_params->filter_date)) {
            $filter_date = explode(" to ", $this->filter_params->filter_date);
            if (count($filter_date) === 2) {
                $fromDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($filter_date[0]))->format('Y-m-d');
                $toDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($filter_date[1]))->format('Y-m-d');
            }
        }

        $quotation_summary_total = 0;
        $order_summary_total = 0;
        $dispatch_count_total = 0;

        $sales_order_view = (!$this->user || empty($this->user['company_id'])) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Sales Orders']);
        $quotation_view = (!$this->user || empty($this->user['company_id'])) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Quotation']);
        $dispatch_view = (!$this->user || empty($this->user['company_id'])) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Dispatch']);

        foreach ($this->rows as $customer) {
            $salesOrders = $customer->salesOrder->filter(fn($order) => $order?->sales_order_date >= $fromDate && $order?->sales_order_date <= $toDate);
            $orderSum = $salesOrders->sum('grand_total');
            $orderCount = $salesOrders->count();
            $orderValue = $orderCount > 0 ? '₹' . number_format($orderSum, 2) . ' (' . $orderCount . ')' : '-';

            $quotations = $customer->quotation->filter(fn($quotation) => $quotation?->quotation_date >= $fromDate && $quotation?->quotation_date <= $toDate);
            $quotationSum = $quotations->sum('grand_total');
            $quotationCount = $quotations->count();
            $quotationValue = $quotationCount > 0 ? '₹' . number_format($quotationSum, 2) . ' (' . $quotationCount . ')' : '-';

            $dispatches = $customer->dispatch->filter(fn($dispatch) => $dispatch?->dispatch_date >= $fromDate && $dispatch?->dispatch_date <= $toDate);
            $dispatchCount = $dispatches->count();

            $quotation_summary_total += $quotationSum;
            $order_summary_total += $orderSum;
            $dispatch_count_total += $dispatchCount;

            $baseRow = [
                $customer->customer_name . ' - ' . ($customer->contact_person_name ?? ''),
                optional($customer->customerType)->name ?? '-',
                optional($customer->state)->name . ' - ' . optional($customer->city)->name,
            ];


            if ($quotation_view) {
                $baseRow[] = $quotationValue ?? '-';
            }

            if ($sales_order_view) {
                $baseRow[] = $orderValue ?? '-';
            }

            if ($dispatch_view) {
                $baseRow[] = $dispatchCount ?? '-';
            }

            $hasCompanyColumn = false;
            if (!$this->user || empty($this->user['company_id'])) {
                array_splice($baseRow, 1, 0, [optional($customer->company)->company_name ?? '-']);
                $hasCompanyColumn = true;
            }

            $orders = SalesOrder::with(['sales_status'])
                ->where('company_id', $customer->company_id)
                ->where('customer_id', $customer->id)
                ->whereBetween('sales_order_date', [$fromDate, $toDate])
                ->get();

            $Quotation = Quotation::with(['sales_status'])
                ->where('company_id', $customer->company_id)
                ->where('customer_id', $customer->id)
                ->whereBetween('quotation_date', [$fromDate, $toDate])
                ->get();

            $Dispatch = Dispatch::with('dispatch_items')
                ->where('company_id', $customer->company_id)
                ->where('customer_id', $customer->id)
                ->whereBetween('dispatch_date', [$fromDate, $toDate])
                ->get();

            $maxRows = max($Quotation->count(), $orders->count(), $Dispatch->count());

            if ($maxRows === 0) {
                $finalRows->push(array_merge($baseRow, array_fill(0, 12, '')));
                continue;
            }

            for ($i = 0; $i < $maxRows; $i++) {
                $quotation = $Quotation[$i] ?? null;
                $order = $orders[$i] ?? null;
                $dispatch = $Dispatch[$i] ?? null;

                $quotationRow = $quotation_view ? [
                    $quotation?->quotation_no ?? '-',
                    $quotation?->quotation_date ? Carbon::parse($quotation->quotation_date)->format('d-m-Y') : '-',
                    $quotation?->grand_total ?? 0,
                    optional($quotation?->sales_status)->statusname ?? '-',
                ] : [];

                $orderRow = $sales_order_view ? [
                    $order?->sales_order_no ?? '-',
                    $order?->sales_order_date ? Carbon::parse($order->sales_order_date)->format('d-m-Y') : '-',
                    $order?->grand_total ?? 0,
                    optional($order?->sales_status)->statusname ?? '-',
                ] : [];

                $dispatchRow = $dispatch_view ? [
                    $dispatch?->dispatch_no ?? '-',
                    $dispatch?->dispatch_date ? Carbon::parse($dispatch->dispatch_date)->format('d-m-Y') : '-',
                    $dispatch?->dispatch_items->sum('dispatch_quantity') ?? '-',
                ] : [];

                $finalRows->push(array_merge(
                    $i === 0 ? $baseRow : array_fill(0, count($baseRow), ''),
                    $quotationRow,
                    $orderRow,
                    $dispatchRow
                ));
            }
        }

        // Summary row
        $summaryRow = array_fill(0, count($finalRows[0] ?? []), '');
        $stateIndex = $hasCompanyColumn ? 3 : 2;
        $quotationIndex = $hasCompanyColumn ? 4 : 3;
        $orderIndex = $quotationIndex + ($quotation_view ? 1 : 0);
        $dispatchIndex = $orderIndex + ($sales_order_view ? 1 : 0);

        $summaryRow[$stateIndex] = 'Grand Total';
        if ($quotation_view) $summaryRow[$quotationIndex] = '₹' . number_format($quotation_summary_total, 2);
        if ($sales_order_view) $summaryRow[$orderIndex] = '₹' . number_format($order_summary_total, 2);
        if ($dispatch_view) $summaryRow[$dispatchIndex] = $dispatch_count_total;

        $finalRows->push($summaryRow);

        return $finalRows;
    }


    public function headings(): array
    {
        $headings = [
            'Customer Name',
            'Customer Type',
            'Customer State City',
            'Quotation Value',
            'Order Value',
            'Total Dispatch',
            'Quotation No',
            'Quotation Date',
            'Quotation Total Amount',
            'Quotation Sales Status',
            'Sales Order No',
            'Sales Order Date',
            'Sales Order Total Amount',
            'Sales Order Sales Status',
            'Dispatch No',
            'Dispatch Date',
            'Dispatch QTY',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, ['Company Name']);
        }

        $sales_order_view = (!$this->user || empty($this->user['company_id'])) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Sales Orders']);
        $quotation_view = (!$this->user || empty($this->user['company_id'])) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Quotation']);
        $dispatch_view = (!$this->user || empty($this->user['company_id'])) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Dispatch']);

        if (!$quotation_view) {
            $headings = array_filter($headings, fn($col) =>
                !in_array($col, ['Quotation Value', 'Quotation No', 'Quotation Date', 'Quotation Total Amount', 'Quotation Sales Status']));
        }

        if (!$sales_order_view) {
            $headings = array_filter($headings, fn($col) =>
                !in_array($col, ['Order Value', 'Sales Order No', 'Sales Order Date', 'Sales Order Total Amount', 'Sales Order Sales Status']));
        }

        if (!$dispatch_view) {
            $headings = array_filter($headings, fn($col) =>
                !in_array($col, ['Total Dispatch', 'Dispatch No', 'Dispatch Date', 'Dispatch QTY']));
        }

        return array_values($headings);
    }


    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }

    private function parseDate($range, $index)
    {
        $parts = explode(" to ", $range);
        return count($parts) === 2
            ? \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[$index]))->format('Y-m-d')
            : null;
    }
}
