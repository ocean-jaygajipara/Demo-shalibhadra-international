<?php

namespace App\Exports;

use App\Models\ProductCombo;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductComboExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = ProductCombo::with([
                    'company',
                    'displayUnit',
                    'product',
                    'product_category'
                ])
            ->whereNull('deleted_at');


        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }

        if ($this->filter_params?->company) {
            $query->where('company_id', $this->filter_params->company);
        }


        if (!empty($this->filter_params->product_as_variant_price_id)) {
            $query->whereHas('combo_items', function ($q) {
                $q->where('product_variant_price_id', $this->filter_params->product_as_variant_price_id);
            });
        }

        if ($this->filter_params?->display_unit_id) {
            $query->whereHas('displayUnit', function ($q) {
                $q->where('id', $this->filter_params->display_unit_id);
            });
        }

        if (!empty($this->filter_params->status) && $this->filter_params->status !== 'all') {
            $query->where('status', $this->filter_params->status);
        }

        if (!empty($this->filter_params->search)) {
            $search = $this->filter_params->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_combo_name', 'like', "%{$search}%")
                    ->orWhere('price', 'like', "%{$search}%");
            });
        }
        if (!empty($this->filter_params->filter_created_by)) {
            $query->where('created_by', $this->filter_params->filter_created_by);
        } elseif ($this->user && $this->user->company_id && empty($this->modules['all_data_permission'])) {
            if (!empty($this->modules['personal_data_permission'])) {
                $query->where('created_by', $this->user->id);
            }
        }
        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Company Name',
            'Combo Name',
            'Product Category Name',
            'Display Unit',
            'Sub products',
            'Status',
        ];
    }

    public function map($combo): array
    {
        // Build product name (variant / code / price) same as in Blade
        $comboItems = [];

        foreach ($combo->combo_items as $item) {

            $product_name = '';
            $item = (object) $item;

            $product_name = '';
            $product_name = $item?->product?->part_no ?? '';
            $product_name .= ' - ' . $item?->product?->product_name ?? '';

            if (!empty($product_name)) {
                $comboItems[] = $product_name;
            }
        }

        $variantCodePrice = implode(', <br> ', $comboItems); // Use newline separator for Excel

        return [
            $this->srNo++,
            optional($combo->company)->company_name ?? '-',
            $combo->product_combo_name ?? '-',
            $combo->product_category?->name ?? '-',
            optional($combo->displayUnit)->name ?? '-',
            $variantCodePrice,
            $combo->status === 'active' ? 'Active' : 'Inactive',
        ];
    }


    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }
}
