<script>
    $(document).on('click', '#openProductBOMModal', function () {
        $('#product_bom_modal form')[0].reset();

        $('#product_bom_modal .is-invalid').removeClass('is-invalid');
        $('#product_bom_modal .invalid-feedback').remove();

        $('#product_bom_modal .modal-title').text('Add Product BOM');
        $('#product_bom_modal').modal('show');

        $('#bom_product_category_id').val(null).trigger('change');
        $('#bom_product_variant_price_id').val(null).trigger('change');
        $(".search_by_product_bom_category").attr("data-selectedCategoryId", "");
        $(".search_by_bom_product").attr("data-selectedProductVariantPriceId", "");
        fetchProductBOMCategory();
        fetch_product_bom_variant_price();
    });

    $(document).on('keydown', function (e) {
        if (e.key === "Escape" || e.keyCode === 27) {
            let $openSelect2 = $('.select2-container--open');
            if ($openSelect2.length > 0) {
                let $select = $openSelect2.prev('select');
                if ($select.length && $select.data('select2')) {
                    $select.select2('close');
                    e.stopPropagation();
                    e.preventDefault();
                }
            }
        }
    });

    function loadBomTabData() {
        let baseUrl = routes.bom;

        if (!baseUrl) {
            $('#tabContent').html('<p class="text-danger">Invalid tab.</p>');
            return;
        }

        let product_id = $("#product_id").val();
        let url = baseUrl + '?product_id=' + encodeURIComponent(product_id);

        $('.product-bom-section').show();
        if ($.fn.DataTable.isDataTable('#yajra-datatables-bom')) {
            $('#yajra-datatables-bom').DataTable().destroy();
            $('#table-headers-bom').empty();
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
                    $('#table-headers-bom').append(`<th>${col.td_label ?? col.name}</th>`);
                });

                $('#yajra-datatables-bom').DataTable({
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
                $('#tabContent').html('<p class="text-danger">Failed to load BOM data.</p>');
            }
        });
    }


    $(document).ready(function () {
        $('#product_bom_submit').on('click', function (e) {
            e.preventDefault();

            let form = $('#productBOMForm')[0];
            let formData = new FormData(form);

            let company_id = $(".search_by_company").val() || $('meta[name="company_id"]').attr('value');
            let product_id = $("#product_id").val();
            formData.append("company_id", company_id);
            formData.append("product_id", product_id);
            formData.append("status", 'active');

            let edit_product_bom_id = $('#edit_product_bom_id').val();
            let product_bom_url = edit_product_bom_id
                ? `{{ route('product-bom.update', ['product_bom' => ':id']) }}`.replace(':id', edit_product_bom_id)
                : `{{ route($product_bom_route . '.store') }}`;

            if (edit_product_bom_id) {
                formData.append('_method', 'PUT');
            }

            $('#product_bom_submit').attr('disabled', true).text('Submitting...');

                $.ajax({
                    url: product_bom_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function () {
                        $('#productBOMForm').find('.is-invalid').removeClass('is-invalid');
                        $('#productBOMForm').find('.invalid-feedback').remove();
                    },
                    success: function (res) {
                        $('#product_bom_submit').attr('disabled', false).text('Submit');

                        if (res.status === true) {
                            $('#product_bom_modal').modal('hide');
                            $('#productBOMForm')[0].reset();
                            $('#edit_product_bom_id').val('');

                            loadBomTabData();
                            toastr.success(res.message);
                        }
                    },
                    error: function (xhr) {
                        $('#product_bom_submit').attr('disabled', false).text('Submit');

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                let input = $('#productBOMForm').find('[name="' + field + '"]');
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


        $(document).on('click', '.edit_product_bom', function () {
            let productBomId = $(this).data('id');

            $.ajax({
                url: "{{ route($product_bom_route . '.index') }}/" + productBomId + "/edit",
                type: "GET",
                success: function (response) {
                    if (response.status) {
                        let bom = response.data;


                        $("#material_name").val(bom.material_name);
                        $("#last_process").val(bom.last_process);
                        $("#required_material_qty").val(bom.required_material_qty);
                        $("#for_product_qty").val(bom.for_product_qty);
                        $("#use_in_operation").val(bom.use_in_operation);

                        $(".search_by_product_bom_category").attr("data-selectedCategoryId",bom.bom_product_category_id);
                        $(".search_by_bom_product").attr("data-selectedProductVariantPriceId",bom.bom_product_variant_price_id);

                        $("#edit_product_bom_id").val(bom.id);
                        $('#product_bom_modal .modal-title').text('Edit Product BOM');
                        fetchProductBOMCategory();
                        fetch_product_bom_variant_price();
                        $('.modal').modal('hide');
                        $('#product_bom_modal').modal('show');
                    } else {
                        toastr.error("Failed to fetch BOM data");
                    }
                },
                error: function () {
                    toastr.error("Something went wrong while fetching BOM");
                }
            });
        });
    });
</script>
