<script>
    let productAsVariantApiRequest = null; // Declare globally, outside the event

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_productAsVariant();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_productAsVariant();
    });

    $(document).on('change', '.search_by_product_category', function () {
        fetch_productAsVariant();
    });

    function fetch_productAsVariant() {

        // Abort previous request if any
        if (productAsVariantApiRequest !== null) {
            productAsVariantApiRequest.abort();
        }

        // console.log("product variant L-3");
        let instance = $('.product_as_variant_price_id');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let tax_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }


        if (!tax_id) {
            tax_id = instance.attr("data-selectedTaxId");
        }

        let category_id = $(".search_by_product_category option:selected").val();
        if (!category_id) {
            category_id = $("#product_category_id").val();
        }
        variantPrices = null;

        if (company_id && category_id) {
            // console.log("product_as_variant_price_id L-36");
            productAsVariantApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('api.get_product_with_variant') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                    category_id: category_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Product</option>";
                        if (response.data && response.data.total > 0) {
                            variantPrices = [];
                            $.each(response?.data?.items, function(i, item) {
                                let optionLabel = item?.product_code + ' - ' + item?.product_name;
                                item = {
                                    ...item,
                                    "label": optionLabel
                                };
                                variantPrices.push(item);


                                if (tax_id == item.id) {
                                    options += "<option value='" + item?.id +
                                        "' data-price='" + item?.price +
                                        "' selected >" + item?.label + "</option>";
                                } else {
                                    options += "<option value='" + item?.id +
                                        "' data-price='" + item?.price +
                                        "' >" + item?.label + "</option>";
                                }
                            });
                        }

                        if ($(".product_as_variant_price_id").attr('data-append')) {
                            if ($(document).find("." + $(".product_as_variant_price_id").attr(
                                    'data-append'))
                                .length > 0) {
                                $("." + $(".product_as_variant_price_id").attr('data-append')).empty();
                                $("." + $(".product_as_variant_price_id").attr('data-append')).append(
                                    options);
                            } else {
                                $("." + $(".product_as_variant_price_id").attr('data-append')).empty();
                                $("." + $(".product_as_variant_price_id").attr('data-append')).append(
                                    options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="product_variant_id" class="form-select product_as_variant_price_id';
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
                                '<div class="form-group"> <label for="product_variant_id">Select Product Variant ';
                            if (is_required) {
                                html += '<span class="text-danger">*</span>';
                            }
                            html += '</label>';
                            html += select_tag;
                            html += '</div></div>';
                            $(".country_div").remove();
                            instance.parent().parent().after(html);
                        } else if (instance.parent("tr")) {
                            console.log("getState 113", instance, instance.parent(), instance.next("th"));
                            $(".product_as_variant_price_id").removeClass('d-none').empty().append(options);
                            /*
                            // $(".search_by_country").remove();
                            // instance.parent().next("th").remove();
                            // instance.parent().after('<th>'+options+'</th>');
                            */
                        } else {
                            // console.log("getState 82", instance.parent());
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
