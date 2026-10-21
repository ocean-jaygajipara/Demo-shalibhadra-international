<script>
    let productUnitApiRequest = null; // Declare globally, outside the event
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_productUnit();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_productUnit();
    });

    function fetch_productUnit() {
        // Abort previous request if any
        if (productUnitApiRequest !== null) {
            // return "";
            productUnitApiRequest.abort();
        }
        console.log("product unit L-16");
        let instance = $('.search_by_display_unit');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let display_unit_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!display_unit_id) {
            display_unit_id = $('.search_by_display_unit').attr("data-selectedDisplayUnitId");
        }

        if (company_id) {

            productUnitApiRequest =
                $.ajax({
                    type: 'POST',
                    url: "{{ route('get-product-unit') }}",
                    'beforeSend': function(request) {
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                            'content'));
                    },
                    data: {
                        company_id: company_id,
                    },
                    success: function(response) {
                        if (response.status) {
                            let options = "<option value=''>Select Display Unit</option>";
                            if (response.data && response.data.length > 0) {
                                $.each(response.data, function(i, item) {
                                    if (display_unit_id == item.id) {
                                        options += "<option value='" + item.id +
                                            "' selected >" + item.product_unit + "</option>";
                                    } else {
                                        options += "<option value='" + item.id +
                                            "'>" +
                                            item.product_unit + "</option>";
                                    }
                                });
                            }
                            if ($(".search_by_display_unit").attr('data-append')) {
                                if ($(document).find("." + $(".search_by_display_unit").attr('data-append'))
                                    .length > 0) {
                                    $("." + $(".search_by_display_unit").attr('data-append')).empty();
                                    $("." + $(".search_by_display_unit").attr('data-append')).append(options);
                                } else {
                                    $("." + $(".search_by_display_unit").attr('data-append')).empty();
                                    $("." + $(".search_by_display_unit").attr('data-append')).append(options);
                                }
                            } else if (instance.parent().parent().hasClass("col-md-6") || instance
                                .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                                .hasClass("col-md-3")) {

                                let select_tag =
                                    '<select name="display_unit_id" class="form-select search_by_display_unit';
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
                                    '<div class="form-group"> <label for="display_unit_id">Select Display unit ';
                                if (is_required) {
                                    html += '<span class="text-danger">*</span>';
                                }
                                html += '</label>';
                                html += select_tag;
                                html += '</div></div>';
                                $(".country_div").remove();
                                instance.parent().parent().after(html);
                            } else if (instance.parent("tr")) {
                                // console.log("getState 39", instance, instance.parent(), instance.next("th"));
                                $(".search_by_display_unit").removeClass('d-none').empty().append(options2);
                                /*
                                // $(".search_by_country").remove();
                                // instance.parent().next("th").remove();
                                // instance.parent().after('<th>'+options+'</th>');
                                */
                            } else {
                                console.log("getState 82", instance.parent());
                                instance.parent().after(options);
                            }
                            // $('.search_by_state').trigger("change");
                            $('.select2').select2();
                        }
                    }
                });

            // fetch_orderUnit();
        }
    }
    // $('.search_by_company').trigger("change");
</script>
