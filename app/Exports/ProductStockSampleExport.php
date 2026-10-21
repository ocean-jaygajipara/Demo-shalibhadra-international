<?php

namespace App\Exports;

use App\Models\ProductVariantPrice;
use App\Models\ProductStockHistory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class ProductStockSampleExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $companyId;
    protected $warehouseId;

    public function __construct($companyId, $warehouseId)
    {
        $this->companyId = $companyId;
        $this->warehouseId = $warehouseId;
    }

    public function collection()
    {
        $stocks = ProductStockHistory::select(
            'product_variant_price_id',
            DB::raw('SUM(total_quantity) as total_quantity')
        )
            ->where('company_id', $this->companyId)
            ->where('warehouse_id', $this->warehouseId)
            ->groupBy('product_variant_price_id')
            ->get();

        $result = new Collection();
        $srNo = 1;

        foreach ($stocks as $stock) {
            $variantPrice = ProductVariantPrice::with(['product', 'variant'])
                ->find($stock->product_variant_price_id);

            if (!$variantPrice) {
                continue;
            }

            $productName = $variantPrice->product->product_name ?? 'N/A';
            $productCode = $variantPrice->product_code ?? 'N/A';
            $variantName = $variantPrice->variant->name ?? '';

            $combinedName = $variantName
                ? ($variantName . ' - ' . $productName)
                : $productName;

            $result->push([
                'Sr No.' => $srNo++,
                'Product Name' => $combinedName,
                'Product Code' => $productCode,
                'Available Product Stock' => $stock->total_quantity,
                'Add New Product Stock' => '',
            ]);
        }



        return $result;
    }

    public function headings(): array
    {
        return [
            'Sr No.',
            'Product Name',
            'Product Code',
            'Available Product Stock',
            'Add New Product Stock',
        ];
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
                $sheet = $event->sheet->getDelegate();

                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->getStyle("A1:{$highestColumn}1")
                    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
