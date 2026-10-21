<?php

namespace App\Imports;

use App\Models\CompanyDetails;
use App\Models\MasterUnit;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\ProductTAX;
use App\Models\ProductVariant;
use App\Models\ProductImportFile;
use App\Models\ProductVariantPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToCollection, WithHeadingRow
{
    protected $company_id;
    protected $loginUserId;
    protected $currentGuard;
    protected $productImportFileId;

    public function __construct($company_id, $loginUserId, $currentGuard, $productImportFileId)
    {
        $this->company_id = $company_id;
        $this->loginUserId = $loginUserId;
        $this->currentGuard = $currentGuard;
        $this->productImportFileId = $productImportFileId;
    }

    public function collection(Collection $collection)
    {
        Log::warning('🚀 ProductImport started', [
            'rows_count' => $collection->count(),
            'company_id' => $this->company_id,
            'user_id' => $this->loginUserId,
            'file_id' => $this->productImportFileId,
        ]);

        $totalProducts = 0;
        $totalSuccessProducts = 0;
        $totalFailedProducts = 0;
        $errors = [];

        $companyDetails = CompanyDetails::where('company_id', $this->company_id)->first();
        $duplicationCheckType = $companyDetails?->duplication_check_type ?? 'variant';

        foreach ($collection as $index => $row) {
            if ($row->filter()->isEmpty()) {
                $rowNumber = $index + 2;
                Log::debug("🔹 Row {$rowNumber} is empty. Skipping...");
                continue;
            }

            $totalProducts++;
            $rowNumber = $index + 2;

            try {
                // Extract and trim
                $product_category_name    = trim($row['product_category_name'] ?? '');
                $product_sub_category_name = trim($row['product_sub_category_name'] ?? '');
                $product_code             = trim($row['product_code'] ?? '');
                $product_name             = trim($row['product_name'] ?? '');
                $product_tax              = trim($row['product_tax'] ?? '');
                $display_unit             = trim($row['display_unit'] ?? '');
                $hsn_code                 = trim($row['hsn_code'] ?? '');
                $product_variant_name     = trim($row['product_variant_name'] ?? '');
                $product_description      = trim($row['product_description'] ?? '');
                $product_weight           = trim($row['product_weight_gm'] ?? '');
                $price                    = trim($row['price'] ?? '');
                $inner_pack_qty           = trim($row['inner_pack_qty'] ?? '');
                $outer_pack_qty           = trim($row['outer_pack_qty'] ?? '');
                $min_stock_qty            = trim($row['min_stock_qty'] ?? '');
                $max_stock_qty            = trim($row['max_stock_qty'] ?? '');
                $including_gst            = trim($row['including_gst'] ?? '');

                // Validate required fields
                $validationErrors = [];
                if (empty($product_category_name))     $validationErrors[] = 'Product Category Name is required';
                if (empty($product_sub_category_name)) $validationErrors[] = 'Product Sub Category Name is required';
                if (empty($product_name))              $validationErrors[] = 'Product Name is required';
                if (empty($product_tax))               $validationErrors[] = 'Product Tax is required';
                if (empty($product_variant_name))      $validationErrors[] = 'Product Variant Name is required';
                if (empty($product_code))              $validationErrors[] = 'Product Code is required';
                if (!isset($price) || !is_numeric($price)) {
                    $validationErrors[] = 'Price is required and must be numeric';
                }

                if (!empty($validationErrors)) {
                    $totalFailedProducts++;
                    $errors[] = "Row $rowNumber: " . implode(', ', $validationErrors);
                    Log::warning("❌ Validation failed at row {$rowNumber}", [
                        'errors' => $validationErrors,
                        'row_data' => $row->toArray(),
                    ]);
                    continue;
                }

                // Check for display unit
                $unit = null;
                if (!empty($display_unit)) {
                    $unit = MasterUnit::where('company_id', $this->company_id)
                        ->where('name', $display_unit)
                        ->first();

                    if (!$unit) {
                        Log::info("ℹ️ Display Unit '{$display_unit}' not found at row {$rowNumber}. Setting display_unit_id as null.");
                        $unit = null;
                    }
                }

                // Create/find category and subcategory
                $category = ProductCategory::firstOrCreate(
                    ['name' => $product_category_name, 'company_id' => $this->company_id],
                    ['created_by' => $this->loginUserId]
                );

                $subcategory = ProductSubcategory::firstOrCreate(
                    [
                        'name' => $product_sub_category_name,
                        'company_id' => $this->company_id,
                        'product_category_id' => $category->id
                    ],
                    ['created_by' => $this->loginUserId]
                );

                $variant = ProductVariant::firstOrCreate(
                    ['name' => $product_variant_name, 'company_id' => $this->company_id],
                    ['created_by' => $this->loginUserId]
                );

                // Get product tax
                $productTax = ProductTAX::where('company_id', $this->company_id)
                    ->where('tax_value', $product_tax)
                    ->where('status', 'active')
                    ->first();

                $product_tax_id = $productTax?->id;

                // Check for duplicates based on duplication_check_type
                $product = null;
                $skipInsertion = false;

                if ($duplicationCheckType === 'product') {
                    // Check if product already exists based on name, category, and subcategory
                    // $existingProduct = Product::where('company_id', $this->company_id)
                    //     ->where('product_category_id', $category->id)
                    //     ->where('product_sub_category_id', $subcategory->id)
                    //     ->where('product_name', $product_name)
                    //     ->first();

                    // if ($existingProduct) {
                    //     Log::warning("⚠️ Duplicate product found at row {$rowNumber}", [
                    //         'product_name' => $product_name,
                    //         'category' => $product_category_name,
                    //         'subcategory' => $product_sub_category_name,
                    //         'existing_product_id' => $existingProduct->id
                    //     ]);
                    //     $errors[] = "Row {$rowNumber}: Duplicate product '{$product_name}' in category '{$product_category_name}' and subcategory '{$product_sub_category_name}'. Skipped.";
                    //     $totalFailedProducts++;
                    //     $skipInsertion = true;
                    // }
                } elseif ($duplicationCheckType === 'variant') {
                    $productCodeExists = ProductVariantPrice::where('product_code', $product_code)->exists();

                    if ($productCodeExists) {
                        Log::warning("⚠️ Duplicate product_code '{$product_code}' found at row {$rowNumber}. Skipping insertion.", [
                            'product_code' => $product_code,
                            'duplication_check_type' => $duplicationCheckType
                        ]);
                        $errors[] = "Row {$rowNumber}: Duplicate product_code '{$product_code}' found. Skipped.";
                        $totalFailedProducts++;
                        $skipInsertion = true;
                    }
                }

                if ($skipInsertion) {
                    continue;
                }

                // Find existing product for variant price insertion (allow multiple products with same name for variant type)
                $product = Product::where('company_id', $this->company_id)
                    ->where('product_category_id', $category->id)
                    ->where('product_sub_category_id', $subcategory->id)
                    ->where('product_name', $product_name)
                    ->first();

                // Create product if it doesn't exist
                if (!$product) {
                    $product = Product::create([
                        'company_id'              => $this->company_id,
                        'product_category_id'     => $category->id,
                        'product_sub_category_id' => $subcategory->id,
                        'product_code'            => $product_code,
                        'product_tax_id'          => $product_tax_id,
                        'product_name'            => $product_name,
                        'display_unit_id'         => $unit?->id,
                        'product_description'     => $product_description,
                        'hsn_code'                => $hsn_code,
                        'created_by'              => $this->loginUserId,
                        'status'                  => 'active',
                    ]);

                    Log::info("✅ Product created", [
                        'row' => $rowNumber,
                        'product_id' => $product->id,
                        'product_name' => $product->product_name,
                        'product_code' => $product->product_code,
                        'category' => $product_category_name,
                        'subcategory' => $product_sub_category_name,
                    ]);
                } else {
                    Log::info("♻️ Product already exists", [
                        'row' => $rowNumber,
                        'product_id' => $product->id,
                        'product_name' => $product->product_name,
                    ]);
                }

                // For variant type, we've already checked product_code duplication above
                // No need for additional variant price duplication check

                // Insert product variant price
                ProductVariantPrice::create([
                    'product_id'      => $product->id,
                    'variant_id'      => $variant->id,
                    'product_code'    => $product_code,
                    'including_gst'   => strtolower($including_gst) === 'yes' || $including_gst === '1' ? 1 : 0,
                    'description'     => $product_description,
                    'product_weight'  => $product_weight,
                    'price'           => $price ?? 0,
                    'outer_pack_qty'  => is_numeric($outer_pack_qty) ? (int)$outer_pack_qty : 0,
                    'inner_pack_qty'  => is_numeric($inner_pack_qty) ? (int)$inner_pack_qty : 0,
                    'min_stock_qty'   => is_numeric($min_stock_qty) ? (int)$min_stock_qty : 0,
                    'max_stock_qty'   => is_numeric($max_stock_qty) ? (int)$max_stock_qty : 0,
                    'status'          => 'active',
                    'created_by'      => $this->loginUserId,
                ]);

                Log::info("💲 Variant price added", [
                    'row' => $rowNumber,
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'product_code' => $product_code,
                    'price' => $price,
                ]);

                $totalSuccessProducts++;

            } catch (\Exception $e) {
                $totalFailedProducts++;
                $errorMsg = "Row $rowNumber: Insert failed - " . $e->getMessage();
                $errors[] = $errorMsg;
                Log::error("🔥 Exception at row {$rowNumber}", [
                    'message' => $e->getMessage(),
                    'row_data' => $row->toArray(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('✅ ProductImport finished', [
            'file_id' => $this->productImportFileId,
            'company_id' => $this->company_id,
            'duplication_check_type' => $duplicationCheckType,
            'total_products' => $totalProducts,
            'total_success' => $totalSuccessProducts,
            'total_failed' => $totalFailedProducts,
        ]);

        ProductImportFile::where('id', $this->productImportFileId)->update([
            'total_products'         => $totalProducts,
            'total_success_products' => $totalSuccessProducts,
            'total_failed_products'  => $totalFailedProducts,
            'errors'                 => json_encode($errors),
        ]);
    }
}
