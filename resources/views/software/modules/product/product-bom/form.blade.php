<div class="modal fade" id="product_bom_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add Product BOM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form name="productBOMForm" id="productBOMForm" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <input type="hidden" name="edit_product_bom_id" id="edit_product_bom_id" value="">

                        {{-- Product Category --}}
                        <div class="col-12 col-md-6 mb-2">
                            <label class="form-label">Product Category <span class="text-danger">*</span></label>
                            <select name="bom_product_category_id" id="bom_product_category_id"
                                class="form-control @error('bom_product_category_id') is-invalid @enderror search_by_product_bom_category select2"
                                data-append="search_by_product_bom_category"
                                data-selectedCategoryId="{{ isset($data) && $data?->bom_product_category_id ? $data?->bom_product_category_id : '' }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('bom_product_category_id', $data->bom_product_category_id ?? '') ? '' : 'selected' }}>
                                    Select Product Category
                                </option>
                            </select>
                            @error('bom_product_category_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Product --}}
                        <div class="col-12 col-md-6 mb-2">
                            <label class="form-label">Product <span class="text-danger">*</span></label>
                            <select name="bom_product_variant_price_id" id="bom_product_variant_price_id"
                                class="form-control @error('bom_product_variant_price_id') is-invalid @enderror search_by_bom_product select2"
                                data-append="search_by_bom_product"
                                data-selectedProductVariantPriceId="{{ isset($data) && $data?->bom_product_variant_price_id ? $data?->bom_product_variant_price_id : '' }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('bom_product_variant_price_id', $data->bom_product_variant_price_id ?? '') ? '' : 'selected' }}>
                                    Select Product
                                </option>
                            </select>
                            @error('bom_product_variant_price_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-2 d-none">
                            <div class="form-group">
                                <label class="form-label">Material Name <span class="text-danger">*</span></label>
                                <input id="material_name" type="text"
                                    class="form-control @error('material_name') is-invalid @enderror" name="material_name"
                                    placeholder="Enter Material Name">
                                @error('material_name')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Last Process -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Last Process </label>
                                <input id="last_process" type="text"
                                    class="form-control @error('last_process') is-invalid @enderror" name="last_process"
                                    placeholder="Enter Last Process">
                                @error('last_process')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Required Material Quantity -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Required Material Weight (Gram) <span class="text-danger">*</span></label>
                                <input id="required_material_qty" type="number" step="0.001"
                                    class="form-control @error('required_material_qty') is-invalid @enderror" name="required_material_qty"
                                    placeholder="e.g. 10.500">
                                @error('required_material_qty')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- For Product Quantity -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">For Product Qty <span class="text-danger">*</span></label>
                                <input id="for_product_qty" type="number"
                                    class="form-control @error('for_product_qty') is-invalid @enderror" name="for_product_qty"
                                    placeholder="Enter quantity">
                                @error('for_product_qty')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Use in Operation -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Use In Operation <span class="text-danger">*</span></label>
                                <input id="use_in_operation" type="text"
                                    class="form-control @error('use_in_operation') is-invalid @enderror" name="use_in_operation"
                                    placeholder="Enter operation details">
                                @error('use_in_operation')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>


                        <div class="modal-footer">
                            <button type="button" id="product_bom_submit" class="btn btn-primary">Submit</button>
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
