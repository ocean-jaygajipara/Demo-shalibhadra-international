<!-- Wrap with a div and hide by default -->
<!-- Product BOM Section -->
<div class="product-bom-section" style="display: none;">
    <div class="d-flex justify-content-end align-items-center mb-3">
        <button type="button" class="btn btn-primary waves-effect waves-light" id="openProductBOMModal">
            <i class="fa fa-plus"></i> Add New BOM
        </button>
    </div>

    <!-- Table/content below -->
    <div class="row my-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-datatable text-nowrap mt-3">
                    <div class="card-datatable table-responsive">
                        <table id="yajra-datatables-bom" class="dt-responsive table table-hover product-bom">
                            <thead>
                                <tr id="table-headers-bom"></tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

