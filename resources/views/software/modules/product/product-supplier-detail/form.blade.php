<!-- Modal for adding Supplier Detail -->
<div class="modal fade" id="product_supplier_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add Product Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form name="productSupplierForm" id="productSupplierForm" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <input type="hidden" name="edit_product_supplier_detail_id" id="edit_product_supplier_detail_id" value="">

                        <!-- Supplier Name -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Supplier Name <span class="text-danger">*</span></label>
                                <input id="supplier_name" type="text"
                                    class="form-control @error('supplier_name') is-invalid @enderror"
                                    name="supplier_name" placeholder="Enter Supplier Name">
                                @error('supplier_name')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Process -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Process <span class="text-danger">*</span></label>
                                <input id="process" type="text"
                                    class="form-control @error('process') is-invalid @enderror"
                                    name="process" placeholder="Enter Process">
                                @error('process')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Rate -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Rate <span class="text-danger">*</span></label>
                                <input id="rate" type="number" step="0.001"
                                    class="form-control @error('rate') is-invalid @enderror"
                                    name="rate" placeholder="Enter Rate">
                                @error('rate')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" id="product_supplier_submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
