<!-- Wrap with a div and hide by default -->
<!-- Product customer Item Section -->
<div class="product-customer-item-section" style="display: none;">
    <div class="d-flex justify-content-end align-items-center mb-3">
        <button type="button" class="btn btn-primary waves-effect waves-light" id="openProductCustomerItemModal">
            <i class="fa fa-plus"></i> Add New Customer Item
        </button>
    </div>

    <!-- Table/content below -->
    <div class="row my-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-datatable text-nowrap mt-3">
                    <div class="card-datatable table-responsive">
                        <table id="yajra-datatables-customer-item" class="dt-responsive table table-hover product-customer-item">
                            <thead>
                                <tr id="table-headers-customer-item"></tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

