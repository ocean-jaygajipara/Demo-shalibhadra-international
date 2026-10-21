<!-- Wrap with a div and hide by default -->
<!-- Product Supplier Section -->
<div class="product-supplier-section" style="display: none;">
    <div class="d-flex justify-content-end align-items-center mb-3">
        <button type="button" class="btn btn-primary waves-effect waves-light" id="openProductSupplierModal">
            <i class="fa fa-plus"></i> Add New Supplier
        </button>
    </div>

    <!-- Table/content below -->
    <div class="row my-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-datatable text-nowrap mt-3">
                    <div class="card-datatable table-responsive">
                        <table id="yajra-datatables-product-supplier" class="dt-responsive table table-hover product-supplier">
                            <thead>
                                <tr id="table-headers-product-supplier"></tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
