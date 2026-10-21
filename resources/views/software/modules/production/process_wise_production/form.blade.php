@extends('software.layout.app')
@section('title', $modules['title'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">{{ isset($edit) ? 'Edit' : 'Create' }} {{ $modules['title'] }}</h5>
            </div>
            <div class="card-body mt-3">
                <form action="{{ isset($edit) ? route($modules['route'].'.update', $edit->id) : route($modules['route'].'.store') }}" method="POST">
                    @csrf
                    @if(isset($edit))
                        @method('PUT')
                    @endif
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="{{ old('date', $edit->date ?? date('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Item Code</label>
                            <select name="product_id" id="product_id" class="form-select select2">
                                <option value="">Select Item</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" 
                                            data-part_no="{{ $product->part_no }}" 
                                            data-stock_kg="{{ $product->stock_kg }}" 
                                            data-stock_qty="{{ $product->stock_qty }}" 
                                            data-gross_wt="{{ $product->gross_wt }}" 
                                            data-net_wt="{{ $product->net_wt }}" 
                                            {{ (isset($edit) && $edit->product_id == $product->id) ? 'selected' : '' }}>
                                        {{ $product->product_name }}{{ $product->part_no ? ' - ' . $product->part_no : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Description</label>
                            <input type="text" id="description" class="form-control bg-light" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stock</label>
                            <input type="text" name="stock" id="stock" class="form-control bg-light" value="{{ old('stock') }}" disabled>
                        </div>
                    </div>

               

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Process</label>
                            <input type="text" name="process" class="form-control" value="{{ old('process', $edit->process ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Weight</label>
                            <input type="number" step="any" name="weight" id="weight" class="form-control" value="{{ old('weight', $edit->weight ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Wt / Pc</label>
                            <input type="number" step="any" name="wt_pc" id="wt_pc" class="form-control" value="{{ old('wt_pc', $edit->wt_pc ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Qty</label>
                            <input type="number" name="qty" id="qty" class="form-control" value="{{ old('qty', $edit->qty ?? '') }}">
                        </div>
                    </div>

                         <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Prev. Process</label>
                            <input type="text" name="prev_process" class="form-control bg-light" value="" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Weight</label>
                            <input type="number" step="any" id="prev_weight" name="prev_weight" class="form-control bg-light" value="" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Wt / Pc</label>
                            <input type="number" step="any" id="prev_wt_pc" name="prev_wt_pc" class="form-control bg-light" value="" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Qty</label>
                            <input type="number" id="prev_qty" name="prev_qty" class="form-control bg-light" value="" readonly>
                        </div>
                    </div>
              
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Warehouse</label>
                            <select name="warehouse_id" class="form-select select2">
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ (isset($edit) && $edit->warehouse_id == $warehouse->id) ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Location</label>
                            <select name="location_id" class="form-select select2">
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ (isset($edit) && $edit->location_id == $location->id) ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#used_materials" role="tab">Used Materials</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#by_products" role="tab">By Products</a>
                        </li>
                    </ul>
                    
                    <div class="tab-content border border-top-0 p-3">
                        <div class="tab-pane active" id="used_materials" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm" id="used_materials_table">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th style="min-width: 250px;">Material Name</th>
                                            <th style="min-width: 200px;">Used From Warehouse</th>
                                            <th style="min-width: 200px;">Used From Location</th>
                                            <th style="min-width: 150px;">Last Process</th>
                                            <th style="min-width: 150px;">Batch No</th>
                                            <th style="min-width: 150px;">Stock</th>
                                            <th style="min-width: 150px;">Machine Stock</th>
                                            <th style="min-width: 150px;">Used Qty</th>
                                            <th style="min-width: 150px;">Used Wt</th>
                                            <th style="min-width: 150px;">Wt/Pc</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($edit) && $edit->used_materials->count() > 0)
                                            @foreach($edit->used_materials as $index => $item)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td><select name="used_materials[{{$index}}][material_id]" class="form-select select2">
                                                        <option value="">Select Material</option>
                                                        @foreach($materials as $material)
                                                            <option value="{{ $material->id }}" data-category_id="{{ $material->product_category_id }}" data-stock_kg="{{ $material->stock_kg }}" {{ $item->material_id == $material->id ? 'selected' : '' }}>{{ $material->product_name }}</option>
                                                        @endforeach
                                                    </select></td>
                                                    <td><select name="used_materials[{{$index}}][used_from_godown_id]" class="form-select select2">
                                                        <option value="">Select</option>
                                                        @foreach($warehouses as $warehouse)
                                                            <option value="{{ $warehouse->id }}" {{ $item->used_from_godown_id == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                                        @endforeach
                                                    </select></td>
                                                    <td><select name="used_materials[{{$index}}][used_from_location_id]" class="form-select select2">
                                                        <option value="">Select</option>
                                                        @foreach($locations as $location)
                                                            <option value="{{ $location->id }}" {{ $item->used_from_location_id == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                                        @endforeach
                                                    </select></td>
                                                    <td><input type="text" name="used_materials[{{$index}}][last_process]" class="form-control form-control-sm" value="{{ $item->last_process }}"></td>
                                                    <td><input type="text" name="used_materials[{{$index}}][batch_no]" class="form-control form-control-sm" value="{{ $item->batch_no }}"></td>
                                                    <td><input type="number" step="any" name="used_materials[{{$index}}][stock]" class="form-control form-control-sm bg-light" value="{{ $item->stock }}" readonly></td>
                                                    <td><input type="number" step="any" name="used_materials[{{$index}}][machine_stock]" class="form-control form-control-sm" value="{{ $item->machine_stock }}"></td>
                                                    <td><input type="number" name="used_materials[{{$index}}][used_qty]" class="form-control form-control-sm" value="{{ $item->used_qty }}"></td>
                                                    <td><input type="number" step="any" name="used_materials[{{$index}}][used_wt]" class="form-control form-control-sm" value="{{ $item->used_wt }}"></td>
                                                    <td><input type="number" step="any" name="used_materials[{{$index}}][wt_pc]" class="form-control form-control-sm" value="{{ $item->wt_pc }}"></td>
                                                    <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button></td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td>1</td>
                                                <td><select name="used_materials[0][material_id]" class="form-select select2">
                                                    <option value="">Select Material</option>
                                                    @foreach($materials as $material)
                                                        <option value="{{ $material->id }}" data-category_id="{{ $material->product_category_id }}" data-stock_kg="{{ $material->stock_kg }}">{{ $material->product_name }}</option>
                                                    @endforeach
                                                </select></td>
                                                <td><select name="used_materials[0][used_from_godown_id]" class="form-select select2">
                                                    <option value="">Select</option>
                                                    @foreach($warehouses as $warehouse)
                                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                                    @endforeach
                                                </select></td>
                                                <td><select name="used_materials[0][used_from_location_id]" class="form-select select2">
                                                    <option value="">Select</option>
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                    @endforeach
                                                </select></td>
                                                <td><input type="text" name="used_materials[0][last_process]" class="form-control form-control-sm"></td>
                                                <td><input type="text" name="used_materials[0][batch_no]" class="form-control form-control-sm"></td>
                                                <td><input type="number" step="any" name="used_materials[0][stock]" class="form-control form-control-sm bg-light" readonly></td>
                                                <td><input type="number" step="any" name="used_materials[0][machine_stock]" class="form-control form-control-sm"></td>
                                                <td><input type="number" name="used_materials[0][used_qty]" class="form-control form-control-sm"></td>
                                                <td><input type="number" step="any" name="used_materials[0][used_wt]" class="form-control form-control-sm"></td>
                                                <td><input type="number" step="any" name="used_materials[0][wt_pc]" class="form-control form-control-sm"></td>
                                                <td><button type="button" class="btn btn-primary btn-sm add-used-material"><i class="fa fa-plus"></i></button></td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="tab-pane" id="by_products" role="tabpanel">
                             <div class="table-responsive">
                                <table class="table table-bordered table-sm" id="by_products_table">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">Sr.</th>
                                            <th style="min-width: 250px;">Material Name</th>
                                            <th style="min-width: 100px;">Stock</th>
                                            <th style="min-width: 120px;">Produced Wt</th>
                                            <th style="min-width: 120px;">Produced Qty</th>
                                            <th style="min-width: 150px;">Batch No</th>
                                            <th style="min-width: 200px;">Warehouse</th>
                                            <th style="min-width: 200px;">Location</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($edit) && $edit->by_products->count() > 0)
                                            @foreach($edit->by_products as $index => $item)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><select name="by_products[{{$index}}][product_id]" class="form-select select2 by-product-select">
                                                    <option value="">Select Material</option>
                                                    @foreach($materials as $material)
                                                        <option value="{{ $material->id }}" data-category_id="{{ $material->product_category_id }}" {{ $item->product_id == $material->id ? 'selected' : '' }}>{{ $material->product_name }}</option>
                                                    @endforeach
                                                </select></td>
                                                <td><input type="number" step="any" name="by_products[{{$index}}][stock]" class="form-control form-control-sm bg-light" value="{{ $item->stock }}" readonly></td>
                                                <td><input type="number" step="any" name="by_products[{{$index}}][produced_wt]" class="form-control form-control-sm" value="{{ $item->produced_wt }}"></td>
                                                <td><input type="number" name="by_products[{{$index}}][produced_qty]" class="form-control form-control-sm" value="{{ $item->produced_qty }}"></td>
                                                <td><input type="text" name="by_products[{{$index}}][batch_no]" class="form-control form-control-sm" value="{{ $item->batch_no }}"></td>
                                                <td><select name="by_products[{{$index}}][warehouse_id]" class="form-select select2 by-product-warehouse">
                                                    <option value="">Select</option>
                                                    @foreach($warehouses as $warehouse)
                                                        <option value="{{ $warehouse->id }}" {{ $item->warehouse_id == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                                    @endforeach
                                                </select></td>
                                                <td><select name="by_products[{{$index}}][location_id]" class="form-select select2 by-product-location">
                                                    <option value="">Select</option>
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}" {{ $item->location_id == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                                    @endforeach
                                                </select></td>
                                                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button></td>
                                            </tr>
                                            @endforeach
                                        @else
                                        <tr>
                                            <td>1</td>
                                            <td><select name="by_products[0][product_id]" class="form-select select2 by-product-select">
                                                <option value="">Select Material</option>
                                                @foreach($materials as $material)
                                                    <option value="{{ $material->id }}" data-category_id="{{ $material->product_category_id }}">{{ $material->product_name }}</option>
                                                @endforeach
                                            </select></td>
                                            <td><input type="number" step="any" name="by_products[0][stock]" class="form-control form-control-sm bg-light" readonly></td>
                                            <td><input type="number" step="any" name="by_products[0][produced_wt]" class="form-control form-control-sm"></td>
                                            <td><input type="number" name="by_products[0][produced_qty]" class="form-control form-control-sm"></td>
                                            <td><input type="text" name="by_products[0][batch_no]" class="form-control form-control-sm"></td>
                                            <td><select name="by_products[0][warehouse_id]" class="form-select select2 by-product-warehouse">
                                                <option value="">Select</option>
                                                @foreach($warehouses as $warehouse)
                                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                                @endforeach
                                            </select></td>
                                            <td><select name="by_products[0][location_id]" class="form-select select2 by-product-location">
                                                <option value="">Select</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                @endforeach
                                            </select></td>
                                            <td><button type="button" class="btn btn-primary btn-sm add-by-product"><i class="fa fa-plus"></i></button></td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-success">Save</button>
                        <a href="{{ route($modules['route'].'.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@push('page_scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2();
        
        // Dynamic row logic for used materials can be added here
        let usedMatIndex = 1;
        $(document).on('click', '.add-used-material', function() {
            let row = `<tr>
                <td>${usedMatIndex + 1}</td>
                <td><select name="used_materials[${usedMatIndex}][material_id]" class="form-select select2">
                    <option value="">Select Material</option>
                    @foreach($materials as $material)
                        <option value="{{ $material->id }}" data-category_id="{{ $material->product_category_id }}" data-stock_kg="{{ $material->stock_kg }}">{{ $material->product_name }}</option>
                    @endforeach
                </select></td>
                <td><select name="used_materials[${usedMatIndex}][used_from_godown_id]" class="form-select select2">
                    <option value="">Select</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select></td>
                <td><select name="used_materials[${usedMatIndex}][used_from_location_id]" class="form-select select2">
                    <option value="">Select</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select></td>
                <td><input type="text" name="used_materials[${usedMatIndex}][last_process]" class="form-control form-control-sm"></td>
                <td><input type="text" name="used_materials[${usedMatIndex}][batch_no]" class="form-control form-control-sm"></td>
                <td><input type="number" step="any" name="used_materials[${usedMatIndex}][stock]" class="form-control form-control-sm bg-light" readonly></td>
                <td><input type="number" step="any" name="used_materials[${usedMatIndex}][machine_stock]" class="form-control form-control-sm"></td>
                <td><input type="number" name="used_materials[${usedMatIndex}][used_qty]" class="form-control form-control-sm"></td>
                <td><input type="number" step="any" name="used_materials[${usedMatIndex}][used_wt]" class="form-control form-control-sm"></td>
                <td><input type="number" step="any" name="used_materials[${usedMatIndex}][wt_pc]" class="form-control form-control-sm"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button></td>
            </tr>`;
            $('#used_materials_table tbody').append(row);
            $('.select2').select2();
            usedMatIndex++;
        });

        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
        });

        $(document).on('change', 'select[name$="[material_id]"], select[name$="[used_from_godown_id]"]', function() {
            var tr = $(this).closest('tr');
            var materialSelect = tr.find('select[name$="[material_id]"]');
            var warehouseSelect = tr.find('select[name$="[used_from_godown_id]"]');
            
            var material_id = materialSelect.val();
            var warehouse_id = warehouseSelect.val();
            var category_id = materialSelect.find('option:selected').data('category_id');

            if(material_id && warehouse_id && category_id) {
                $.ajax({
                    url: '{{ route("get-product-stock") }}',
                    type: 'GET',
                    data: {
                        company_id: '{{ auth()->user()->company_id ?? 1 }}',
                        warehouse_id: warehouse_id,
                        product_category_id: category_id,
                        product_variant_price_id: material_id,
                        unit: 'kg'
                    },
                    success: function(response) {
                        if(response.status) {
                            tr.find('input[name$="[stock]"]').val(response.data);
                        } else {
                            tr.find('input[name$="[stock]"]').val('');
                        }
                    },
                    error: function() {
                        tr.find('input[name$="[stock]"]').val('');
                    }
                });
            } else {
                // Do not show any stock if warehouse is not selected
                tr.find('input[name$="[stock]"]').val('');
            }
        });

        $('#product_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var description = selectedOption.data('part_no');
            var stockKg = selectedOption.data('stock_kg');
            var stockQty = selectedOption.data('stock_qty');
            var grossWt = selectedOption.data('gross_wt');
            var netWt = selectedOption.data('net_wt');
            
            $('#description').val(description ? description : '');
            $('#stock').val(stockKg ? stockKg : '0');
            
            var parsedQty = stockQty ? parseInt(stockQty) : 0;
            $('#prev_qty').val(parsedQty ? parsedQty : '0');
            $('#prev_wt_pc').val(netWt ? netWt : '').trigger('input'); // Trigger input to update by products
            $('#prev_weight').val(grossWt ? grossWt : '');
        });

        // Removed qty * wt_pc calculation per user request

        // Used Materials Calculation
        function calculateUsedMaterialWtPc(tr) {
            var usedWt = parseFloat(tr.find('input[name$="[used_wt]"]').val()) || 0;
            var usedQty = parseFloat(tr.find('input[name$="[used_qty]"]').val());
            var mainQty = parseFloat($('#qty').val()) || 0;
            
            var finalQty = (!isNaN(usedQty) && usedQty > 0) ? usedQty : mainQty;
            
            if (usedWt > 0 && finalQty > 0) {
                tr.find('input[name$="[wt_pc]"]').val((usedWt / finalQty).toFixed(3));
            } else {
                tr.find('input[name$="[wt_pc]"]').val('');
            }
        }

        $(document).on('input', 'input[name$="[used_wt]"], input[name$="[used_qty]"]', function() {
            calculateUsedMaterialWtPc($(this).closest('tr'));
        });

        $(document).on('input', 'input[name$="[wt_pc]"]', function() {
            var tr = $(this).closest('tr');
            if(tr.closest('#used_materials_table').length > 0) {
                var wtPc = parseFloat($(this).val()) || 0;
                var usedQty = parseFloat(tr.find('input[name$="[used_qty]"]').val());
                var mainQty = parseFloat($('#qty').val()) || 0;
                
                var finalQty = (!isNaN(usedQty) && usedQty > 0) ? usedQty : mainQty;
                
                if (wtPc > 0 && finalQty > 0) {
                    tr.find('input[name$="[used_wt]"]').val((wtPc * finalQty).toFixed(3));
                } else if (wtPc == 0) {
                    tr.find('input[name$="[used_wt]"]').val('');
                }
            }
        });

        // Trigger recalculation on main Qty change
        $('#qty').on('input', function() {
            $('#used_materials_table tbody tr').each(function() {
                calculateUsedMaterialWtPc($(this));
            });
        });

        // By Products Dynamic Row
        let byProductIndex = 1;
        $(document).on('click', '.add-by-product', function() {
            let row = `<tr>
                <td>${byProductIndex + 1}</td>
                <td><select name="by_products[${byProductIndex}][product_id]" class="form-select select2 by-product-select">
                    <option value="">Select Material</option>
                    @foreach($materials as $material)
                        <option value="{{ $material->id }}" data-category_id="{{ $material->product_category_id }}">{{ $material->product_name }}</option>
                    @endforeach
                </select></td>
                <td><input type="number" step="any" name="by_products[${byProductIndex}][stock]" class="form-control form-control-sm bg-light" readonly></td>
                <td><input type="number" step="any" name="by_products[${byProductIndex}][produced_wt]" class="form-control form-control-sm" value="${$('#wt_pc').val() || ''}"></td>
                <td><input type="number" name="by_products[${byProductIndex}][produced_qty]" class="form-control form-control-sm"></td>
                <td><input type="text" name="by_products[${byProductIndex}][batch_no]" class="form-control form-control-sm"></td>
                <td><select name="by_products[${byProductIndex}][warehouse_id]" class="form-select select2 by-product-warehouse">
                    <option value="">Select</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select></td>
                <td><select name="by_products[${byProductIndex}][location_id]" class="form-select select2 by-product-location">
                    <option value="">Select</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button></td>
            </tr>`;
            $('#by_products_table tbody').append(row);
            $('.select2').select2();
            byProductIndex++;
        });

        // Sync Material selection from Used Materials to By Products
        $(document).on('change', 'select[name$="[material_id]"]', function() {
            var nameAttr = $(this).attr('name');
            var match = nameAttr.match(/\[(\d+)\]/);
            if (match && match[1]) {
                var index = match[1];
                var materialId = $(this).val();
                var byProductSelect = $('select[name="by_products['+index+'][product_id]"]');
                
                // If the by product row doesn't exist yet for this index, click add button
                while(byProductSelect.length === 0 && index >= byProductIndex) {
                    $('.add-by-product').trigger('click');
                    byProductSelect = $('select[name="by_products['+index+'][product_id]"]');
                }

                if (byProductSelect.length) {
                    byProductSelect.val(materialId).trigger('change.select2');
                }
            }
        });

        // Sync Wt/Pc with Produced Wt in By Products
        $(document).on('input', '#wt_pc', function() {
            var wtPcValue = $(this).val();
            $('#by_products_table tbody tr').each(function() {
                $(this).find('input[name$="[produced_wt]"]').val(wtPcValue);
            });
        });

        // Trigger change on load if editing
        if ($('#product_id').val()) {
            $('#product_id').trigger('change');
        }
    });
</script>
@endpush
