<script>
    const productCustomerItemStoreUrl = `{{ route($product_custom_item_route . '.store') }}`;
    const productCustomerItemUpdateUrl = `{{ route($product_custom_item_route . '.update', ['product_customer_item' => ':id']) }}`;
    $(document).on('click', '#openProductCustomerItemModal', function () {
        $('#product_customer_item_modal form')[0].reset();

        $('#product_customer_item_modal .is-invalid').removeClass('is-invalid');
        $('#product_customer_item_modal .invalid-feedback').remove();

        $('#product_customer_item_modal .modal-title').text('Add Product Customer Item');
        $('#customer_code').prop('readonly', false);
        $('#customer_item_code').val('');
        $('#product_customer_item_modal').modal('show');
        loadProductCustomerSelect();
    });

    function loadProductCustomerSelect(selectedName = null, callback = null) {
        let company_id = $('.search_by_company').val() || $('meta[name="company_id"]').attr('value');

        // Clear existing options except placeholder
        $('#customer_name').empty().append('<option value="">Select Customer</option>');

        if (!company_id) {
            if (callback) callback();
            return;
        }

        $.ajax({
            url: '{{ env('API_URL') }}get-customer',
            type: 'POST',
            data: { company_id: company_id },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                if (!res.status) {
                    if (callback) callback();
                    return;
                }
                let data = res.data || [];
                data.forEach(function (c) {
                    let opt = $('<option>')
                        .attr('value', c.customer_name || '')
                        .attr('data-client-code', c.client_code || '')
                        .text(c.customer_name || '');
                    $('#customer_name').append(opt);
                });

                if (selectedName) {
                    let found = false;
                    $('#customer_name option').each(function () {
                        if ($(this).text().trim() === selectedName.trim()) {
                            $(this).prop('selected', true);
                            found = true;
                            return false;
                        }
                    });
                    if (!found) {
                        $('#customer_name').val(selectedName);
                    }
                }

                // Initialize or re-initialize select2 with search enabled
                if ($('#customer_name').hasClass('select2-hidden-accessible')) {
                    $('#customer_name').select2('destroy');
                }
                $('#customer_name').wrap('<div class="position-relative"></div>').select2({
                    dropdownParent: $('#customer_name').parent(),
                    minimumResultsForSearch: 0 // Always show search box
                });
                $('#customer_name').trigger('change');
                if (callback) callback();
            },
            error: function (xhr) {
                console.error('Failed to load customers', xhr);
                if (callback) callback();
            }
        });
    }

    function loadCustomerItemDescriptionTabData() {
        let baseUrl = routes.customer_item_description;

        if (!baseUrl) {
            $('#tabContent').html('<p class="text-danger">Invalid tab.</p>');
            return;
        }
        let product_id = $("#product_id").val();
        let url = baseUrl + '?product_id=' + encodeURIComponent(product_id);

        $('.product-customer-item-section').show();
        if ($.fn.DataTable.isDataTable('#yajra-datatables-customer-item')) {
            $('#yajra-datatables-customer-item').DataTable().destroy();
            $('#table-headers-customer-item').empty();
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
                    $('#table-headers-customer-item').append(`<th>${col.td_label ?? col.name}</th>`);
                });

                $('#yajra-datatables-customer-item').DataTable({
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
                $('#tabContent').html('<p class="text-danger">Failed to load product customer item data.</p>');
            }
        });
    }


    $(document).ready(function () {
        $('#product_customer_item_submit').on('click', function (e) {
            e.preventDefault();

            let form = $('#productCustomerItemForm')[0];
            let formData = new FormData(form);

            let company_id = $(".search_by_company").val() || $('meta[name="company_id"]').attr('value');
            let product_id = $("#product_id").val();
            formData.append("company_id", company_id);
            formData.append("product_id", product_id);
            formData.append("status", 'active');

            let edit_product_customer_item_id = $('#edit_product_customer_item_id').val();
            let product_custom_item_url = edit_product_customer_item_id
                ? productCustomerItemUpdateUrl.replace(':id', edit_product_customer_item_id)
                : productCustomerItemStoreUrl;

            if (edit_product_customer_item_id) {
                formData.append('_method', 'PUT');
            }

            $('#product_customer_item_submit').attr('disabled', true).text('Submitting...');

                $.ajax({
                    url: product_custom_item_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function () {
                        $('#productCustomerItemForm').find('.is-invalid').removeClass('is-invalid');
                        $('#productCustomerItemForm').find('.invalid-feedback').remove();
                    },
                    success: function (res) {
                        $('#product_customer_item_submit').attr('disabled', false).text('Submit');

                        if (res.status === true) {
                            $('#product_customer_item_modal').modal('hide');
                            $('#productCustomerItemForm')[0].reset();
                            $('#edit_product_customer_item_id').val('');

                            loadCustomerItemDescriptionTabData();
                            toastr.success(res.message);
                        }
                    },
                    error: function (xhr) {
                        $('#product_customer_item_submit').attr('disabled', false).text('Submit');

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                let input = $('#productCustomerItemForm').find('[name="' + field + '"]');
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


        $(document).on('click', '.edit_product_customer_item', function () {
            let productCustomerItemId = $(this).data('id');

            $.ajax({
                url: "{{ route($product_custom_item_route . '.index') }}/" + productCustomerItemId + "/edit",
                type: "GET",
                success: function (response) {
                    if (response.status) {
                        let customer_item = response.data;

                        $('#customer_code').prop('readonly', false);
                        loadProductCustomerSelect(customer_item.customer_name, function () {
                            let selectedOpt = $('#customer_name').find('option:selected');
                            let clientCode = selectedOpt.data('client-code') || '';
                            if (clientCode) {
                                $("#customer_code").val(clientCode).prop('readonly', true);
                            } else {
                                $("#customer_code").val(customer_item.customer_code || customer_item.customer_item_code || '').prop('readonly', false);
                            }
                        });

                        $("#customer_item_code").val(customer_item.customer_item_code || '');
                        $("#customer_item_description").val(customer_item.customer_item_description);
                        $("#edit_product_customer_item_id").val(customer_item.id);

                        $('#product_customer_item_modal .modal-title').text('Edit Product Customer Item');
                        $('#product_customer_item_modal').modal('show');
                    } else {
                        toastr.error("Failed to fetch Product Customer Item data");
                    }
                },
                error: function () {
                    toastr.error("Something went wrong while fetching Product Customer Item");
                }
            });
        });

        // When customer is selected, auto-fill client code and disable the field
        $(document).on('change', '#customer_name', function () {
            let clientCode = $(this).find('option:selected').data('client-code') || '';
            if (clientCode) {
                $('#customer_code').val(clientCode).prop('readonly', true);
            } else {
                $('#customer_code').val('').prop('readonly', false);
            }
        });
    });
</script>
