<script>
    let productHSNApiRequest = null; // Declare globally, outside the event

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_productHSN();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_productHSN();
    });

    function fetch_productHSN() {

        // Abort previous request if any
        if (productHSNApiRequest !== null) {
            productHSNApiRequest.abort();
        }

        let instance = $('.search_by_product_hsn');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let hsn_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!hsn_id) {
            hsn_id = instance.attr("data-selectedHSNId");
        }

        if (company_id) {

            productHSNApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-product-hsn') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Product HSN</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (hsn_id == item.id) {
                                    options += "<option data-product_tax='"+item.product_tax+"' value='" + item.id +
                                        "' selected >" + item.product_hsn + "</option>";
                                } else {
                                    options += "<option data-product_tax='"+item.product_tax+"' value='" + item.id +
                                        "'>" +
                                        item.product_hsn + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_product_hsn").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_product_hsn").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_product_hsn").attr('data-append')).empty();
                                $("." + $(".search_by_product_hsn").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_product_hsn").attr('data-append')).empty();
                                $("." + $(".search_by_product_hsn").attr('data-append')).append(options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="product_hsn_id" class="form-select search_by_product_hsn';
                            if (is_select2) {
                                select_tag += ' select2 ';
                            }
                            select_tag += '"';
                            if (is_required) {
                                select_tag += ' required ';
                            }
                            select_tag += '>' + options + '</select>';


                            let html = '';
                            if (instance.parent().parent().hasClass("col-md-4")) {
                                html = '<div class="col-md-4 state_div">';
                            } else if (instance.parent().parent().hasClass("col-md-3")) {
                                html = '<div class="col-md-3 state_div">';
                            } else {
                                html = '<div class="col-md-6 state_div">';
                            }
                            html +=
                                '<div class="form-group"> <label for="product_hsn_id">Select Product HSN ';
                            if (is_required) {
                                html += '<span class="text-danger">*</span>';
                            }
                            html += '</label>';
                            html += select_tag;
                            html += '</div></div>';
                            $(".country_div").remove();
                            instance.parent().parent().after(html);
                        } else if (instance.parent("tr")) {
                            $(".search_by_product_hsn").removeClass('d-none').empty().append(options);
                        } else {
                            console.log("getState 82", instance.parent());
                            instance.parent().after(options);
                        }
                        // $('.search_by_state').trigger("change");
                        $('.select2').select2();
                    }
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
