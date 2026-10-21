<?php

namespace App\Exports;

use App\Models\ProductStock;
use App\Models\ProductVariantPrice;
use App\Models\ProductStockHistory;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductStockExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
         // Get latest stock entry per grouping
        $latest = ProductStockHistory::select(DB::raw('MAX(id) as id'))
            ->groupBy('company_id', 'warehouse_id', 'product_category_id', 'product_variant_price_id');

        $query = ProductStockHistory::with([
                'company',
                'product.company',
                'product.product_category',
                'warehouse',
            ])
            ->whereIn('id', $latest);

        if (!empty($this->modules['personal_data_permission'])
            && empty($this->modules['all_data_permission'])
            && !empty($this->modules['company_id']))
        {
            $query->where('created_by', $this->loginUserId);
        }

        if (!empty($this->filter_params->filter_search) || !empty($this->filter_params->filter_search)) {
            $search = $this->filter_params->filter_search;
            $query = $query->when($search, function ($q) use ($search) {
                $q->whereHas('product', function ($p) use ($search) {
                    $p->where('product_name', 'like', "%{$search}%")
                    ->orWhere('part_no', 'like', "%{$search}%")
                    ->orWhere('hsn_code', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($this->filter_params->filter_warehouse) || !empty($this->filter_params->filter_warehouse_id)) {
            $warehouseId = $this->filter_params->filter_warehouse ?? $this->filter_params->filter_warehouse_id;
            $query->where('warehouse_id', $warehouseId);
        }


        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } elseif ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user->company_id);
        }

        // Category filter
        if (!empty($this->filter_params->filter_product_category)) {
            $query->where('product_category_id', $this->filter_params->filter_product_category);
        }

        // Variant filter
        if (!empty($this->filter_params->filter_product_variant_price_id)) {
            $query->where('product_variant_price_id', $this->filter_params->filter_product_variant_price_id);
        }

        // Created by filter
        if (!empty($this->filter_params->filter_created_by)) {
            $query->where('created_by', $this->filter_params->filter_created_by);
        } elseif ($this->user && $this->user->company_id && empty($this->modules['all_data_permission'])) {
            if (!empty($this->modules['personal_data_permission'])) {
                $query->where('created_by', $this->user->id);
            }
        }

        return $query->orderBy('id', 'desc')->get();
    }


    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Warehouse Name',
            'Product Category',
            'Product Name',
            'Product Code',
            'Available Stock Qty',
            'Available Stock Kg',
        ];

        // Add company column if user is not restricted to a single company
        if (!$this->user || empty($this->user->company_id)) {
            array_splice($headings, 1, 0, 'Company Name');
        }

        return $headings;
    }

    public function map($productVariantPrice): array
    {
        // Calculate available stock qty from history

        $row = [
            $this->srNo++,
            $productVariantPrice->warehouse?->name ?? '-',
            $productVariantPrice->product_category?->name ?? '-',
            $productVariantPrice->product?->product_name ?? '-',
            $productVariantPrice->product?->part_no ?? '-',
            number_format($productVariantPrice->total_quantity, 2) ?? '-',
            number_format($productVariantPrice->total_kg, 2) ?? '-',
        ];

        // Add company column if user is not restricted to a single company
        if (!$this->user || empty($this->user->company_id)) {
            array_splice($row, 1, 0, $productVariantPrice->company?->company_name ?? '-');
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
