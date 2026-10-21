<div class="modal fade" id="product_customer_item_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add Product Customer Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form name="productCustomerItemForm" id="productCustomerItemForm" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <!-- Hidden field for editing -->
                        <input type="hidden" name="edit_product_customer_item_id" id="edit_product_customer_item_id" value="">


                        <!-- Customer Name (dynamic dropdown) -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                                <select id="customer_name" name="customer_name" class=" select2 form-select @error('customer_name') is-invalid @enderror">
                                    <option value="">Select Customer</option>
                                </select>
                                @error('customer_name')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Customer Code -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Customer Code <span class="text-danger">*</span></label>
                                <input id="customer_code" type="text"
                                    class="form-control @error('customer_code') is-invalid @enderror" name="customer_code"
                                    placeholder="Enter Customer Code">
                                @error('customer_code')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Customer Item Code -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Customer Item Code</label>
                                <input id="customer_item_code" type="text"
                                    class="form-control @error('customer_item_code') is-invalid @enderror" name="customer_item_code"
                                    placeholder="Enter Customer Item Code">
                                @error('customer_item_code')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Customer Item Description -->
                        <div class="col-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Customer Item Description</label>
                                <textarea id="customer_item_description" name="customer_item_description"
                                    class="form-control @error('customer_item_description') is-invalid @enderror"
                                    placeholder="Enter Customer Item Description"></textarea>
                                @error('customer_item_description')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer mt-3">
                            <button type="button" id="product_customer_item_submit" class="btn btn-primary">Submit</button>
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
