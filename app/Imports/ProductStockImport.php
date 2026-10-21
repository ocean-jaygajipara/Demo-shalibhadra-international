<?php

namespace App\Imports;

use App\Models\ProductStockHistory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProductStockImport implements ToCollection
{
    protected $companyId;
    protected $warehouseId;
    protected $userId;

    public function __construct($companyId, $warehouseId)
    {
        $this->companyId = $companyId;

        $this->warehouseId = $warehouseId;
        $this->userId = Auth::id();
    }



    public function collection(Collection $rows)
    {
        \Log::info("ProductStockImport started");
        $rows->shift();

        $errors = [];

        foreach ($rows as $index => $row) {
            $combinedName = trim($row[1] ?? '');
            $productCode = trim($row[2] ?? '');

            if (empty($combinedName)) {
                $errors[] = "Row {$index}: Empty combined name";
                continue;
            }

            $parts = explode('-', $combinedName);
            if (count($parts) < 2) {
                $errors[] = "Row {$index}: Invalid combined name format '{$combinedName}'";
                continue;
            }

            $variantName = trim($parts[0]);
            $productName = trim($parts[1]);

            if (empty($variantName) || empty($productName)) {
                $errors[] = "Row {$index}: Variant or product name empty";
                continue;
            }

            $variantPrice = ProductVariantPrice::where('product_code', $productCode)->first();
            if (!$variantPrice) {
                $errors[] = "Row {$index}: product_code '{$productCode}' not found in product_variant_prices";
                continue;
            }

            $product = Product::find($variantPrice->product_id);
            $variant = ProductVariant::find($variantPrice->variant_id);

            if (!$product || !$variant) {
                $errors[] = "Row {$index}: Product or Variant not found for variantPrice id {$variantPrice->id}";
                continue;
            }

            $stock = ProductStockHistory::where('company_id', $this->companyId)
                ->where('warehouse_id', $this->warehouseId)
                ->where('product_variant_price_id', $variantPrice->id)
                ->first();

            $availableStockFromExcel = (int)($row[3] ?? 0);
            $addNewStock = (int)($row[4] ?? 0);
            $newQuantity = $availableStockFromExcel + $addNewStock;


            if ($stock) {
                if ($stock->total_quantity == $availableStockFromExcel) {
                    $stock->total_quantity += $addNewStock;
                    $stock->updated_at = now();
                    $stock->save();
                    \Log::info("Row {$index} updated existing stock. New total_quantity: {$stock->total_quantity}");
                } else {
                    $errors[] = "Row {$index}: Quantity mismatch. DB total_quantity ({$stock->total_quantity}) does not match Excel available stock ({$availableStockFromExcel}). Skipped update.";
                    \Log::warning("Row {$index} total_quantity mismatch: DB={$stock->total_quantity}, Excel={$availableStockFromExcel}");
                }
            } else {
                ProductStockHistory::create([
                    'company_id' => $this->companyId,
                    'warehouse_id' => $this->warehouseId,
                    'product_variant_price_id' => $variantPrice->id,
                    'total_quantity' => $newQuantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                \Log::info("Row {$index} inserted new stock record with total_quantity {$newQuantity}");
            }
        }

        if (!empty($errors)) {
            session()->flash('importErrors', $errors);
        }
    }
}
