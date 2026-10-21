<script>
    const productSupplierStoreUrl = `{{ route($product_supplier_detail_route . '.store') }}`;
    const productSupplierUpdateUrl = `{{ route($product_supplier_detail_route . '.update', ['product_supplier_detail' => ':id']) }}`;
    $(document).on('click', '#openProductSupplierModal', function () {
        $('#product_supplier_modal form')[0].reset();

        $('#product_supplier_modal .is-invalid').removeClass('is-invalid');
        $('#product_supplier_modal .invalid-feedback').remove();

        $('#product_supplier_modal .modal-title').text('Add Product Supplier');
        $('#product_supplier_modal').modal('show');
    });

    function loadSupplierDetailsTabData() {
        let baseUrl = routes.supplier_details;

        if (!baseUrl) {
            $('#tabContent').html('<p class="text-danger">Invalid tab.</p>');
            return;
        }
        let product_id = $("#product_id").val();
        let url = baseUrl + '?product_id=' + encodeURIComponent(product_id);

        $('.product-supplier-section').show();
        if ($.fn.DataTable.isDataTable('#yajra-datatables-product-supplier')) {
            $('#yajra-datatables-product-supplier').DataTable().destroy();
            $('#table-headers-product-supplier').empty();
        }

        $.ajax({
            url: url,
            type: "GET",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (!response.columns || !response.data) {
                    $('#tabContent').html('<p class="text-danger">Invalid response structure.</p>');
                    return;
                }

                response.columns.forEach(function (col) {
                    $('#table-headers-product-supplier').append(`<th>${col.td_label ?? col.name}</th>`);
                });

                $('#yajra-datatables-product-supplier').DataTable({
                    data: response.data,
                    columns: response.columns,
                    processing: true,
                    serverSide: false,
                    dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',
                    order: [[0, 'asc']],
                    language: {
                        searchPlaceholder: 'Search...',
                    }
                });
            },
            error: function (xhr) {
                console.error(xhr);
                $('#tabContent').html('<p class="text-danger">Failed to load product Supplier data.</p>');
            }
        });
    }


    $(document).ready(function () {
        $('#product_supplier_submit').on('click', function (e) {
            e.preventDefault();

            let form = $('#productSupplierForm')[0];
            let formData = new FormData(form);

            let company_id = $(".search_by_company").val() || $('meta[name="company_id"]').attr('value');
            let product_id = $("#product_id").val();
            formData.append("company_id", company_id);
            formData.append("product_id", product_id);
            formData.append("status", 'active');

            let edit_product_supplier_detail_id = $('#edit_product_supplier_detail_id').val();
            let product_supplier_url = edit_product_supplier_detail_id
                ? productSupplierUpdateUrl.replace(':id', edit_product_supplier_detail_id)
                : productSupplierStoreUrl;

            if (edit_product_supplier_detail_id) {
                formData.append('_method', 'PUT');
            }

            $('#product_supplier_submit').attr('disabled', true).text('Submitting...');

                $.ajax({
                    url: product_supplier_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function () {
                        $('#productSupplierForm').find('.is-invalid').removeClass('is-invalid');
                        $('#productSupplierForm').find('.invalid-feedback').remove();
                    },
                    success: function (res) {
                        $('#product_supplier_submit').attr('disabled', false).text('Submit');

                        if (res.status === true) {
                            $('#product_supplier_modal').modal('hide');
                            $('#productSupplierForm')[0].reset();
                            $('#edit_product_supplier_detail_id').val('');

                            loadSupplierDetailsTabData();
                            toastr.success(res.message);
                        }
                    },
                    error: function (xhr) {
                        $('#product_supplier_submit').attr('disabled', false).text('Submit');

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                let input = $('#productSupplierForm').find('[name="' + field + '"]');
                                input.addClass('is-invalid');
                                input.after(
                                    '<span class="invalid-feedback d-block"><strong>' + messages[0] + '</strong></span>'
                                );
                            });
                        } else {
                            toastr.error('Something went wrong. Please try again.');
                        }
                    }
                });
        });


        $(document).on('click', '.edit_product_supplier', function () {
            let productSupplierId = $(this).data('id');

            $.ajax({
                url: "{{ route($product_supplier_detail_route . '.index') }}/" + productSupplierId + "/edit",
                type: "GET",
                success: function (response) {
                    if (response.status) {
                        let supplier_detail = response.data;

                        $("#supplier_name").val(supplier_detail.supplier_name);
                        $("#process").val(supplier_detail.process);
                        $("#rate").val(supplier_detail.rate);
                        $("#edit_product_supplier_detail_id").val(supplier_detail.id);
                        $('#product_supplier_modal .modal-title').text('Edit Product Supplier');
                        $('#product_supplier_modal').modal('show');
                    } else {
                        toastr.error("Failed to fetch Product Supplier");
                    }
                },
                error: function () {
                    toastr.error("Something went wrong while fetching Product Supplier");
                }
            });
        });
    });
</script>
