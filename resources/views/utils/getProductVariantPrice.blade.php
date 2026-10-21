<script>

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_product_variant_price();
    }

    $(document).on('change', '.search_by_company', function () {
        fetch_product_variant_price();
    });

    $(document).on('change', '.search_by_product_category', function () {
        let company_id = $(".search_by_company option:selected").val();
        let product_category_id = $(".search_by_product_category option:selected").val();
        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (company_id) {
            fetch_product_variant_price();
        }
    });

    function fetch_product_variant_price() {
        let instance = $('.search_by_product');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');

        let product_category_id = $(".search_by_product_category option:selected").val();

        let product_variant_price_id = '';
        if (!product_variant_price_id) {
            product_variant_price_id = instance.attr("data-selectedProductVariantPriceId");
        }
        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (company_id) {
            $.ajax({
                type: 'POST',
                url: "{{ route('get-product-variant-price') }}",
                'beforeSend': function (request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                    category_id: product_category_id,
                },
                success: function (response) {
                    if (response.status) {
                        let options = "<option value=''>Select Product</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function (i, item) {
                                let grossWt = item.gross_wt ? item.gross_wt : 0;
                                let netWt = item.net_wt ? item.net_wt : 0;
                                if (product_variant_price_id == item.id) {
                                    options += "<option value='" + item.id +
                                        "' data-gross-wt='" + grossWt + "' data-net-wt='" + netWt + "' selected >" + item.product + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "' data-gross-wt='" + grossWt + "' data-net-wt='" + netWt + "'>" +
                                        item.product + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_product").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_product").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_product").attr('data-append')).empty();
                                $("." + $(".search_by_product").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_product").attr('data-append')).empty();
                                $("." + $(".search_by_product").attr('data-append')).append(options);
                            }
                        }
                        $('.select2').select2();
                    }
                }
            });
        }
    }
    $('.search_by_product_category').trigger("change");
</script>