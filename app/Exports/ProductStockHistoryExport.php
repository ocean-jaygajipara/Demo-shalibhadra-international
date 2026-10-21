<?php

namespace App\Exports;

use App\Models\ProductStockHistory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class ProductStockHistoryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;
    protected $filter_params;
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
        // Assuming $this->user and $this->modules are set in your export class
        $modules = $this->modules;
        $loginUserId = $this->user['id'] ?? null;

        $query = ProductStockHistory::with([
            'company',
            'warehouse',
            'product_category',
            'product',
        ]);

        // Permission and filtering logic similar to print()
        if (Auth::guard('team_person')->check()) {
            $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
            $query->where('company_id', $teamPersonCompanyId);

            if (!empty($modules['personal_data_permission']) && empty($modules['all_data_permission'])) {
                $query->where('created_by', $loginUserId);
            }
        } else {
            if (!empty($this->user?->company_id)) {
                $query->where('company_id', $this->user?->company_id);
            }
        }

        $filter = $this->filter_params;

        $query = $query->when($filter->filter_search ?? null, function ($q) use ($filter) {
            $q->whereHas('product', function ($p) use ($filter) {
                $p->where('product_name', 'like', "%{$filter->filter_search}%")
                    ->orWhere('part_no', 'like', "%{$filter->filter_search}%")
                    ->orWhere('hsn_code', 'like', "%{$filter->filter_search}%");
            });
        });

        $query = $query->when($filter->filter_warehouse ?? null, function ($q) use ($filter) {
            $q->where('warehouse_id', $filter->filter_warehouse);
        });
        $query = $query->when($filter->filter_company ?? null, function ($q) use ($filter) {
            $q->where('company_id', $filter->filter_company);
        });
        $query = $query->when($filter->filter_product_category ?? null, function ($q) use ($filter) {
            $q->where('product_category_id', $filter->filter_product_category);
        });
        $query = $query->when($filter->filter_product_variant_price_id ?? null, function ($q) use ($filter) {
            $q->where('product_variant_price_id', $filter->filter_product_variant_price_id);
        });

        if (!empty($filter->filter_date)) {
            $filter_date = explode(" to ", $filter->filter_date);
            $fromDate = null;
            $toDate = null;

            if (count($filter_date) == 2) {
                $fromDate = \App\Helpers\Helper::convert_date($filter_date[0], "d/m/Y", "Y-m-d");
                $toDate = \App\Helpers\Helper::convert_date($filter_date[1], "d/m/Y", "Y-m-d");
            }

            if ($fromDate && $toDate) {
                $query->whereDate('date', ">=", $fromDate);
                $query->whereDate('date', "<=", $toDate);
            } elseif ($fromDate) {
                $query->whereDate('date', $fromDate);
            }
        }

        return $query->orderBy('id', 'DESC')->get();
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Warehouse Name',
            'Product Category',
            'Product',
            'Quantity',
            'Kg',
            'Credit',
            'Debit',
            'Date',
            'Remark',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }

        return $headings;
    }

    public function map($addProductStock): array
    {
        $partName = optional($addProductStock->product)->part_no;
        $productName = optional($addProductStock->product)->product_name;


        $productVariantText = trim("{$partName} - {$productName}", " -");

        $row = [
            $this->srNo++,
            optional($addProductStock->warehouse)->name ?? '-',
            optional($addProductStock->product_category)->name ?? '-',
            $productVariantText ?: '-',
            $addProductStock->total_quantity ?? 0,
            $addProductStock->total_kg ?? 0,
            $addProductStock->credit ?? '-',
            $addProductStock->debit ?? '-',
            $addProductStock->date ? \Carbon\Carbon::parse($addProductStock->date)->format('Y-m-d') : '-',
            $addProductStock->remark ?? '-',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($row, 1, 0, optional($addProductStock->company)->company_name ?? '-');
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
