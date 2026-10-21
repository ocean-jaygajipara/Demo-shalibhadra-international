<script>
    $(document).on('click', '#openProductRmDetailModal', function () {
        $('#product_rm_detail_modal form')[0].reset();

        $('#product_rm_detail_modal .is-invalid').removeClass('is-invalid');
        $('#product_rm_detail_modal .invalid-feedback').remove();

        $('#product_rm_detail_modal .modal-title').text('Add Product RM details');
        $('#product_rm_detail_modal').modal('show');
        fetch_productAlloy();
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

    function loadRmDetailsTabData() {
        let baseUrl = routes.rm_details;

        if (!baseUrl) {
            $('#tabContent').html('<p class="text-danger">Invalid tab.</p>');
            return;
        }

        let product_id = $("#product_id").val();
        let url = baseUrl + '?product_id=' + encodeURIComponent(product_id);

        $('.product-rm-detail-section').show();
        if ($.fn.DataTable.isDataTable('#yajra-datatables-product-rm-detail')) {
            $('#yajra-datatables-product-rm-detail').DataTable().destroy();
            $('#table-headers-product-rm-detail').empty();
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
                    $('#table-headers-product-rm-detail').append(`<th>${col.td_label ?? col.name}</th>`);
                });

                $('#yajra-datatables-product-rm-detail').DataTable({
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
                $('#tabContent').html('<p class="text-danger">Failed to load RM Detail data.</p>');
            }
        });
    }


    $(document).ready(function () {
        $('#product_rm_detail_submit').on('click', function (e) {
            e.preventDefault();

            let form = $('#productRmDetailForm')[0];
            let formData = new FormData(form);

            let company_id = $(".search_by_company").val() || $('meta[name="company_id"]').attr('value');
            let product_id = $("#product_id").val();
            formData.append("company_id", company_id);
            formData.append("product_id", product_id);
            formData.append("status", 'active');

            let edit_product_rm_detail_id = $('#edit_product_rm_detail_id').val();
            let product_rm_detail_url = edit_product_rm_detail_id
                ? `{{ route('product-rm-detail.update', ['product_rm_detail' => ':id']) }}`.replace(':id', edit_product_rm_detail_id)
                : `{{ route($product_rm_detail_route . '.store') }}`;

            if (edit_product_rm_detail_id) {
                formData.append('_method', 'PUT');
            }

            $('#product_rm_detail_submit').attr('disabled', true).text('Submitting...');

                $.ajax({
                    url: product_rm_detail_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function () {
                        $('#productRmDetailForm').find('.is-invalid').removeClass('is-invalid');
                        $('#productRmDetailForm').find('.invalid-feedback').remove();
                    },
                    success: function (res) {
                        $('#product_rm_detail_submit').attr('disabled', false).text('Submit');

                        if (res.status === true) {
                            $('#product_rm_detail_modal').modal('hide');
                            $('#productRmDetailForm')[0].reset();
                            $('#edit_product_rm_detail_id').val('');

                            loadRmDetailsTabData();
                            toastr.success(res.message);
                        }
                    },
                    error: function (xhr) {
                        $('#product_rm_detail_submit').attr('disabled', false).text('Submit');

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                let input = $('#productRmDetailForm').find('[name="' + field + '"]');
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


        $(document).on('click', '.edit_product_rm_detail', function () {
            let productRMDetailId = $(this).data('id');

            $.ajax({
                url: "{{ route($product_rm_detail_route . '.index') }}/" + productRMDetailId + "/edit",
                type: "GET",
                success: function (response) {
                    if (response.status) {

                        let productRmDetail = response.data;

                        $("#size_premium").val(productRmDetail.size_premium);
                        $("#od_mm").val(productRmDetail.od_mm);
                        $("#id_mm").val(productRmDetail.id_mm);
                        $("#shape").val(productRmDetail.shape);
                        $(".search_by_product_alloy").attr("data-selectedAlloyId",productRmDetail.alloy);
                        $("#density").val(productRmDetail.density);
                        $("#tol_plus").val(productRmDetail.tol_plus);
                        $("#tol_minus").val(productRmDetail.tol_minus);

                        $("#edit_product_rm_detail_id").val(productRmDetail.id);
                        $('#product_rm_detail_modal .modal-title').text('Edit Product RM Detail');
                        fetch_productAlloy();
                        $('.modal').modal('hide');
                        $('#product_rm_detail_modal').modal('show');

                        // $('#product_rm_detail_modal').on('shown.bs.modal', function () {
                        //     $('.select2').select2();
                        // });
                    } else {
                        toastr.error("Failed to fetch Product RM Detail data");
                    }
                },
                error: function () {
                    toastr.error("Something went wrong while fetching Product RM Detail");
                }
            });
        });
    });
</script>
