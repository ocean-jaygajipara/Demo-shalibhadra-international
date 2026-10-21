<?php

namespace App\Exports;

use App\Models\SalesOrder;
use App\Models\Customer;
use Carbon\CarbonPeriod;
use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesOrderReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;
    protected $columns = [];
    protected $rows = [];

    public function __construct($filter_params = null, $user = null)
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;

        $this->prepareData(); // <-- Do everything here, once
    }

    protected function prepareData()
    {
        $filter_type = $this->filter_params->filter_type ?? 'day';
        $fromDate = null;
        $toDate = null;

        if (!empty($this->filter_params->sales_date)) {
            $dateRange = explode(" to ", $this->filter_params->sales_date);
            if (count($dateRange) === 2) {
                $fromDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dateRange[0]));
                $toDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dateRange[1]));
            }
        }

        if (!$fromDate || !$toDate) {
            return;
        }

        if ($filter_type === 'month') {
            $period = CarbonPeriod::create($fromDate->copy()->startOfMonth(), '1 month', $toDate->copy()->startOfMonth());
        } elseif ($filter_type === 'year') {
            $startYear = $fromDate->copy()->startOfYear();
            $endYear = $toDate->copy()->startOfYear();
            $period = CarbonPeriod::create($startYear, '1 year', $endYear);
        } else {
            $period = CarbonPeriod::create($fromDate, '1 day', $toDate);
        }

        $dateLabels = collect();
        foreach ($period as $date) {
            if ($filter_type === 'month') {
                $dateLabels->push($date->format('m-Y'));
            } elseif ($filter_type === 'year') {
                $dateLabels->push($date->format('Y'));
            } else {
                $dateLabels->push($date->format('d-m-Y'));
            }
        }

        $this->columns = collect(['Sr No', 'Customer Name', 'Total Sales'])
            ->merge($dateLabels)
            ->values()
            ->toArray();

        $customersQuery = Customer::with('company');
        if (Auth::guard('team_person')->check()) {
            $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
            $customersQuery->where('company_id', $teamPersonCompanyId);
        }
        if (!empty($this->filter_params->company)) {
            $customersQuery->where('company_id', $this->filter_params->company);
        }
        if (!empty($this->filter_params->customer)) {
            $customersQuery->where('id', $this->filter_params->customer);
        }
        if (!empty($this->filter_params->customer_type)) {
            $customersQuery->where('customer_type_id', $this->filter_params->customer_type);
        }

        $customers = $customersQuery->orderByDesc("id")->get();

        $fromDate = Carbon::parse($fromDate)->format('Y-m-d');
        $toDate   = Carbon::parse($toDate)->format('Y-m-d');
        $salesOrders = SalesOrder::whereBetween('sales_order_date', [$fromDate, $toDate])
            ->get()
            ->groupBy('customer_id');

        $srNo = 1;
        $totals = [
            'Sr No' => '',
            'Customer Name' => 'Grand Total',
            'Total Sales' => 0,
        ];
        foreach ($dateLabels as $label) {
            $totals[$label] = 0;
        }

        foreach ($customers as $customer) {
            $orders = $salesOrders[$customer->id] ?? collect();
            $row = [
                'Sr No' => $srNo++,
                'Customer Name' => "{$customer?->customer_name} ({$customer?->contact_person_name})",
                'Total Sales' => $orders->sum('grand_total'),
            ];

            $totals['Total Sales'] += $row['Total Sales'];

            foreach ($dateLabels as $label) {
                $filtered = $orders->filter(function ($order) use ($label, $filter_type) {
                    $orderDate = \Carbon\Carbon::parse($order->sales_order_date);
                    $formattedOrderDate = match ($filter_type) {
                        'month' => $orderDate->format('m-Y'),
                        'year' => $orderDate->format('Y'),
                        default => $orderDate->format('d-m-Y'),
                    };
                    return $formattedOrderDate === $label;
                });

                $sum = $filtered->sum('grand_total');
                $row[$label] = number_format($sum, 2);

                // Accumulate total for each date label
                $totals[$label] += $sum;
            }

            $this->rows[] = $row;
        }

        // Add totals row
        $this->rows[] = $totals;
    }

    public function collection()
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function map($row): array
    {
        return array_map(function ($value) {
            return is_numeric($value) ? number_format($value, 2) : $value;
        }, $row);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }
}
