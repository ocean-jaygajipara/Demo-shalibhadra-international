<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $srNo = 1;
    protected $filter_params;
    protected $exportRows;
    protected $user;
    protected $lastProductName = null;
    protected $mergeRanges = [];
    protected $currentRow = 2; // Start after heading row
    protected $productRowGroups = [];
    protected $modules;
    public function __construct($filter_params = null, $user = null, $modules = [])
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->exportRows = collect();
        $this->modules = $modules;
    }


    public function collection()
    {
        $query = Product::with([
            'company',
            'product_category',
        ]);

        // Filters
        if (!empty($this->filter_params)) {
            $request = $this->filter_params;

            // Filter by company
            if (!empty($request->filter_company)) {
                $query->where('company_id', $request->filter_company);
            } elseif (!empty($this->user?->company_id)) {
                $query->where('company_id', $this->user->company_id);
            }

            // Filter by created_by
            if (!empty($request->filter_created_by)) {
                $query->where('created_by', $request->filter_created_by);
            } elseif (
                $this->user &&
                $this->user->company_id &&
                empty($this->modules['all_data_permission']) &&
                !empty($this->modules['personal_data_permission'])
            ) {
                $query->where('created_by', $this->user->id);
            }

            // Filter by status
            if (
                isset($request->status) &&
                $request->status !== '' &&
                $request->status !== 'all'
            ) {
                $query->where('status', $request->status);
            }

            // Filter by category/sub-category
            if (!empty($request->product_category)) {
                $query->where('product_category_id', $request->product_category);
            }
            if (!empty($request->product_grade)) {
                $query->where('product_grade_id', $request->product_grade);
            }

            // Search filter

            if (!empty($request->search)) {
                $search = $request->search;
                $search_arr = explode("-", $search);

                $query->where(function ($q) use ($search_arr, $search) {
                    $q->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('part_no', 'like', "%{$search}%")
                    ->orWhere('hsn_code', 'like', "%{$search}%")
                    ->orWhere('product_mrp', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhere('variant_of', 'like', "%{$search}%");
                });
            }
        }

        $products = $query->orderBy('product_name')->get();

        // Prepare export rows
        foreach ($products as $product) {
            $startRow = $this->currentRow;

            $this->exportRows->push([
                    'product'      => $product,
                ]);
            $this->currentRow++;
            $endRow = $this->currentRow - 1;

            if ($endRow > $startRow) {
                $this->mergeRanges[] = ['start' => $startRow, 'end' => $endRow];
            }
        }

        return $this->exportRows;
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Product Name',
            'Product Category',
            'Part No.',
            'Product MRP',
            'Status',
        ];

        if (!$this->exportRows || empty($this->user['company_id'])) {
            array_splice($headings, 4, 0, 'Company Name'); // Insert after Sr No
        }
        return $headings;
    }

    public function map($row): array
    {
        $product = $row['product'];

        $productName = $product->product_name;
        $isFirstRowOfProduct = !isset($this->productRowGroups[$productName]);

        if ($isFirstRowOfProduct) {
            $this->productRowGroups[$productName] = $this->srNo++;
            $srNo = $this->productRowGroups[$productName];
            $name = $productName;
        } else {
            $srNo = '';
            $name = '';
        }

        $data = [
            $srNo,
            $name,
            optional($product->product_category)->name ?? '-',
            $product->part_no ?? '',
            $product->product_mrp ?? '',
            $product->status == 1 ? 'Active' : 'Inactive',
           // $product->status
        ];

        if (empty($this->user['company_id'])) {
            array_splice($data, 4, 0, optional($product->company)->company_name ?? '-');
        }

        return $data;
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                foreach ($this->mergeRanges as $range) {
                    $start = $range['start'];
                    $end = $range['end'];

                    $columns = ['A', 'B', 'D', 'E', 'F', 'G']; // ✅ Red-bordered columns included

                    foreach ($columns as $col) {
                        $event->sheet->getDelegate()->mergeCells("{$col}{$start}:{$col}{$end}");
                        $event->sheet->getStyle("{$col}{$start}:{$col}{$end}")
                            ->getAlignment()->setVertical('center');
                    }
                }
            },
        ];
    }
}
