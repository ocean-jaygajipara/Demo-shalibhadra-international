<script>
    $(document).on('click', '#openProductImageModal', function () {
        $('#product_image_modal form')[0].reset();

        $('#product_image_modal .is-invalid').removeClass('is-invalid');
        $('#product_image_modal .invalid-feedback').remove();

        $('#product_image_modal .modal-title').text('Add Product Image');
        $('#product_image_modal').find("#image_path_display").html('');
        $('#product_image_modal').modal('show');
    });

    function loadImageTabData() {
        let baseUrl = routes.image;

        if (!baseUrl) {
            $('#tabContent').html('<p class="text-danger">Invalid tab.</p>');
            return;
        }

        let product_id = $("#product_id").val();
        let url = baseUrl + '?product_id=' + encodeURIComponent(product_id);

        $('.product-images-section').show();
        if ($.fn.DataTable.isDataTable('#yajra-datatables-product-images')) {
            $('#yajra-datatables-product-images').DataTable().destroy();
            $('#table-headers-product-images').empty();
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
                    $('#table-headers-product-images').append(`<th>${col.td_label ?? col.name}</th>`);
                });

                $('#yajra-datatables-product-images').DataTable({
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
                $('#tabContent').html('<p class="text-danger">Failed to load product image data.</p>');
            }
        });
    }


    $(document).ready(function () {
        $('#product_image_submit').on('click', function (e) {
            e.preventDefault();

            let form = $('#productImageForm')[0];
            let formData = new FormData(form);

            let company_id = $(".search_by_company").val() || $('meta[name="company_id"]').attr('value');
            let product_id = $("#product_id").val();
            formData.append("company_id", company_id);
            formData.append("product_id", product_id);
            formData.append("status", 'active');

            let edit_product_image_id = $('#edit_product_image_id').val();
            let product_image_url = edit_product_image_id
                ? `{{ route('product-image.update', ['product_image' => ':id']) }}`.replace(':id', edit_product_image_id)
                : `{{ route($product_image_route . '.store') }}`;

            if (edit_product_image_id) {
                formData.append('_method', 'PUT');
            }

            $('#product_image_submit').attr('disabled', true).text('Submitting...');

                $.ajax({
                    url: product_image_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function () {
                        $('#productImageForm').find('.is-invalid').removeClass('is-invalid');
                        $('#productImageForm').find('.invalid-feedback').remove();
                    },
                    success: function (res) {
                        $('#product_image_submit').attr('disabled', false).text('Submit');

                        if (res.status === true) {
                            $('#product_image_modal').modal('hide');
                            $('#productImageForm')[0].reset();
                            $('#edit_product_image_id').val('');

                            loadImageTabData();
                            toastr.success(res.message);
                        }
                    },
                    error: function (xhr) {
                        $('#product_image_submit').attr('disabled', false).text('Submit');

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                let input = $('#productImageForm').find('[name="' + field + '"]');
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


        $(document).on('click', '.edit_product_image', function () {
            let productImageId = $(this).data('id');

            $.ajax({
                url: "{{ route($product_image_route . '.index') }}/" + productImageId + "/edit",
                type: "GET",
                success: function (response) {
                    if (response.status) {
                        let product_image = response.data[0];
                        $("#image_name").val(product_image.image_name);

                        $("#image_path_display").html(`
                            <div class="d-flex gap-2 mt-3">
                                <a href="${product_image.image_url}" target="_blank" class="btn btn-primary btn-sm d-flex align-items-center">
                                    <i class="fa fa-eye me-1"></i> View
                                </a>

                                <a href="${product_image.image_url}" target="_blank" class="btn btn-primary btn-sm d-flex align-items-center" download>
                                    <i class="fa fa-download me-1"></i> Download
                                </a>
                            </div>
                        `);

                        $("#edit_product_image_id").val(product_image.id);
                        $('#product_image_modal .modal-title').text('Edit Product Image');
                        $('#product_image_modal').modal('show');
                    } else {
                        toastr.error("Failed to fetch Product Image data");
                    }
                },
                error: function () {
                    toastr.error("Something went wrong while fetching Product Image");
                }
            });
        });
    });
</script>
