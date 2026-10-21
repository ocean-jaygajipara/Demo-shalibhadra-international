<?php

namespace App\Exports;

use App\Models\SalesOrderItems;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ProductSalesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;
    protected $columns = [];
    protected $rows = [];
    protected $dateLabels = [];

    public function __construct($filter_params = null, $user = null)
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;

        $this->prepareData();
    }

    protected function prepareData()
    {
        $filter_type = $this->filter_params->filter_type ?? 'day';
        $fromDate = null;
        $toDate = null;

        if (!empty($this->filter_params->filter_sales_date)) {
            $filter_sales_date = explode(' to ', $this->filter_params->filter_sales_date);
            if (count($filter_sales_date) === 2) {
                $fromDate = Carbon::createFromFormat('d/m/Y', trim($filter_sales_date[0]));
                $toDate = Carbon::createFromFormat('d/m/Y', trim($filter_sales_date[1]));
            }
        }

        if (!$fromDate || !$toDate) return;

        if ($filter_type === 'month') {
            $period = CarbonPeriod::create($fromDate->copy()->startOfMonth(), '1 month', $toDate->copy()->startOfMonth());
        } elseif ($filter_type === 'year') {
            $period = CarbonPeriod::create($fromDate->copy()->startOfYear(), '1 year', $toDate->copy()->startOfYear());
        } else {
            $period = CarbonPeriod::create($fromDate, '1 day', $toDate);
        }

        foreach ($period as $date) {
            $this->dateLabels[] = match ($filter_type) {
                'month' => $date->format('m-Y'),
                'year' => $date->format('Y'),
                default => $date->format('d-m-Y'),
            };
        }

        $fromDate = Carbon::parse($fromDate)->format('Y-m-d');
        $toDate   = Carbon::parse($toDate)->format('Y-m-d');
        $query = SalesOrderItems::with([
                'sales_order.customer',
                'product_variant_price.variant',
                'product_variant_price.product'
            ]);

        if (Auth::guard('team_person')->check()) {
            $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
            $query->where('company_id', $teamPersonCompanyId);
        }
        $query->whereHas('sales_order', function ($q) use ($fromDate, $toDate) {
            $q->whereBetween('sales_order_date', [$fromDate, $toDate]);

            if (!empty($this->filter_params->filter_company)) {
                $q->where('company_id', $this->filter_params->filter_company);
            }

            if (!empty($this->filter_params->filter_customer)) {
                $q->where('customer_id', $this->filter_params->filter_customer);
            }
        });
        $query->whereHas('product_variant_price.product', function ($q) {
            if (!empty($this->filter_params->filter_product_category)) {
                $q->where('product_category_id', $this->filter_params->filter_product_category);
            }

            if (!empty($this->filter_params->filter_product_subcategory)) {
                $q->where('product_sub_category_id', $this->filter_params->filter_product_subcategory);
            }
        });
        $query->whereHas('product_variant_price', function ($q) {
            if (!empty($this->filter_params->filter_product_variant)) {
                $q->where('id', $this->filter_params->filter_product_variant);
            }
        });
        $salesOrderItems = $query->get();

        $grouped = $salesOrderItems->groupBy(function ($item) {
            return $item?->sales_order?->customer_id . '-' . $item?->product_variant_price?->variant?->id;
        });

        $this->columns = ['SR No', 'Customer Name', 'Product Name', 'Total_price', 'Total_qty'];
        foreach ($this->dateLabels as $label) {
            $this->columns[] = $label . '_price';
            $this->columns[] = $label . '_qty';
        }

        foreach ($grouped as $items) {
            $firstItem = $items->first();
            $customer = $firstItem->sales_order->customer;
            $variant = optional($firstItem->product_variant_price)->variant;
            $product = optional($firstItem->product_variant_price)->product;

            $row = [
                'sr_no' => $this->srNo++,
                'customer_name' => optional($customer)->customer_name ?? '-',
                'product_name' => ($product->product_name ?? 'N/A') . ' - ' .
                    ($variant->name ?? 'N/A') . ' - ' .
                    ($firstItem->product_variant_price->product_code ?? 'N/A'),
                'total_price' => $items->sum('sub_total'),
                'total_qty' => $items->sum('quantity'),
            ];

            foreach ($this->dateLabels as $label) {
                $filtered = $items->filter(function ($item) use ($label, $filter_type) {
                    $date = Carbon::parse($item->sales_order->sales_order_date);
                    $formatted = match ($filter_type) {
                        'month' => $date->format('m-Y'),
                        'year' => $date->format('Y'),
                        default => $date->format('d-m-Y'),
                    };
                    return $formatted === $label;
                });

                $row[$label . '_price'] = $filtered->sum('sub_total');
                $row[$label . '_qty'] = $filtered->sum('quantity');
            }

            $this->rows[] = $row;
        }

        // Totals
        $rowsCollection = collect($this->rows);
        $totals = [
            'sr_no' => '',
            'customer_name' => '',
            'product_name' => 'TOTAL',
            'total_price' => $rowsCollection->sum('total_price'),
            'total_qty' => $rowsCollection->sum('total_qty'),
        ];

        foreach ($this->dateLabels as $label) {
            $totals[$label . '_price'] = $rowsCollection->sum($label . '_price');
            $totals[$label . '_qty'] = $rowsCollection->sum($label . '_qty');
        }

        $this->rows[] = $totals;
    }

    public function collection()
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        $firstRow = ['SR No', 'Customer Name', 'Product Name', 'Price', 'Qty'];
        $secondRow = ['', '', '', '', ''];

        foreach ($this->dateLabels as $label) {
            $firstRow[] = $label;
            $firstRow[] = '';
            $secondRow[] = 'Price';
            $secondRow[] = 'Qty';
        }

        return [$firstRow, $secondRow];
    }

    public function map($row): array
    {
        return array_map(function ($value) {
            return is_numeric($value) ? number_format($value, 2) : $value;
        }, $row);
    }

    public function styles(Worksheet $sheet)
    {
        $totalRowIndex = count($this->rows) + 2;
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
            $totalRowIndex => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2']
                ]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $startCol = 6;
                foreach ($this->dateLabels as $index => $label) {
                    $colIndexStart = $startCol + $index * 2;
                    $colA = Coordinate::stringFromColumnIndex($colIndexStart);
                    $colB = Coordinate::stringFromColumnIndex($colIndexStart + 1);
                    $event->sheet->mergeCells("{$colA}1:{$colB}1");
                    $event->sheet->getStyle("{$colA}1")->getAlignment()->setHorizontal('center');
                }
            },
        ];
    }
}
