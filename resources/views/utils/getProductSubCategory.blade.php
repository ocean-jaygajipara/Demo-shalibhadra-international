<script>
    let productSubCategoryApiRequest = null; // Declare globally, outside the event

    $(document).on('change', '.search_by_product_category', function() {
        fetchProductSubCategory();
    });

    function fetchProductSubCategory() {
        // Abort previous request if any
        if (productSubCategoryApiRequest !== null) {
            productSubCategoryApiRequest.abort();
        }
        console.log("product subcategory L-3");

        let instance = $('.search_by_product_subcategory');
        let company_id = $(".search_by_company option:selected").val();
        let category_id = $(".search_by_product_category option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let subcategory_id = '';


        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!subcategory_id) {
            subcategory_id = instance.attr("data-selectedSubcategoryId");
        }

        console.log("product subcategory L-15", company_id, category_id);
        if (company_id && category_id) {
            console.log("search_by_subcategory L-31");
            productSubCategoryApiRequest =  $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}product/sub-category/list',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                    product_category_id: category_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Product Subcategory</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (subcategory_id == item.id) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.name + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.name + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_product_subcategory").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_product_subcategory").attr(
                                    'data-append'))
                                .length > 0) {
                                $("." + $(".search_by_product_subcategory").attr('data-append'))
                                    .empty();
                                $("." + $(".search_by_product_subcategory").attr('data-append'))
                                    .append(options);
                            } else {
                                $("." + $(".search_by_product_subcategory").attr('data-append'))
                                    .empty();
                                $("." + $(".search_by_product_subcategory").attr('data-append'))
                                    .append(options);
                            }
                            filter_product_sub_category = $(
                                'select[name="product_subcategory_id"] option:selected').val();
                            if ($.trim(filter_product_sub_category || '') !== '') {
                                $('.search_by_product_subcategory').trigger("change");
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="product_subcategory_id" class="form-select search_by_product_subcategory';
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
                                '<div class="form-group"> <label for="product_subcategory_id">Select Product Subcategory ';
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
                            $(".search_by_area").removeClass('d-none').empty().append(options);
                            /*
                            // $(".search_by_country").remove();
                            // instance.parent().next("th").remove();
                            // instance.parent().after('<th>'+options+'</th>');
                            */
                        } else {
                            console.log("getState 82", instance.parent());
                            instance.parent().after(options);
                        }
                        // fetch_product_variant_price();
                        $('.select2').select2();
                    }
                }
            });
        }
    }

    $('.search_by_product_category').trigger("change");
</script>
