<script>
    let productCategoryApiRequest = null; // Declare globally, outside the event
    let productCategoryApiLoading = false; // Flag to track if request is in progress

    if ($('meta[name="company_id"]').attr('value')) {
        filter_company = $('meta[name="company_id"]').attr('value');
        if ($.trim(filter_company || '') !== '') {
            fetchProductCategory();
        }
    }



    $(document).on('change', '.search_by_company', function() {
        filter_company = $('select[name="company_id"] option:selected').val();
        if ($.trim(filter_company || '') !== '') {
            fetchProductCategory();
        }
    });

    function fetchProductCategory() {
        // Prevent duplicate calls if request is already in progress
        if (productCategoryApiLoading) {
            console.log("Product category API request already in progress, skipping duplicate call");
            return;
        }

        // console.log("product category L-3");
        let instance = $('.search_by_product_category');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let category_id = '';
        let defaultFirst = instance.attr("data-defaultFirst");

        if (!category_id) {
            category_id = instance.attr("data-selectedCategoryId");
        }
        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            // console.log("search_by_category L-31");
            // Abort previous request if any
            if (productCategoryApiRequest !== null) {
                productCategoryApiRequest.abort();
            }
            productCategoryApiLoading = true; // Set loading flag
            productCategoryApiRequest = $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}product/category/list',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    productCategoryApiLoading = false; // Reset loading flag on success
                    if (response.status) {
                        let options = "<option value=''>Select Product Category</option>";
                        if (response.data && response.data.length > 0) {
                            // Build options
                            $.each(response.data, function(i, item) {
                                let isSelected = false;
                                // Check if category_id is set and matches
                                if (category_id && category_id == item.id) {
                                    isSelected = true;
                                } 
                                // If category_id is empty/undefined/null, use defaultFirst
                                else if ((!category_id || category_id === '' || category_id === 'undefined' || category_id === null) && defaultFirst) {
                                    
                                    let defaultFirstLower = String(defaultFirst || '').toLowerCase().trim();
                                    let itemNameLower = String(item?.name || '').toLowerCase().trim();
                                    
                                    // console.log("defaultFirst L-65", defaultFirst, defaultFirstLower, itemNameLower);

                                    if (item?.id == defaultFirst || itemNameLower == defaultFirstLower) {
                                        isSelected = true;
                                    }
                                }
                                
                                if (isSelected) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.name + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.name + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_product_category").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_product_category").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_product_category").attr('data-append')).empty();
                                $("." + $(".search_by_product_category").attr('data-append')).append(
                                    options);
                            } else {
                                $("." + $(".search_by_product_category").attr('data-append')).empty();
                                $("." + $(".search_by_product_category").attr('data-append')).append(
                                    options);
                            }

                            filter_product_category = $(
                                'select[name="product_category_id"] option:selected').val();
                            if ($.trim(filter_product_category || '') !== '') {
                                //console.log("product category called");
                                $('.search_by_product_category').trigger("change");
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="product_category_id" class="form-select search_by_product_category';
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
                                '<div class="form-group"> <label for="product_category_id">Select Product Category ';
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
                            // console.log("getState 82", instance.parent());
                            instance.parent().after(options);
                        }
                        // $('.search_by_state').trigger("change");
                        $('.select2').select2();
                    }
                },
                error: function(xhr, status, error) {
                    productCategoryApiLoading = false; // Reset loading flag on error
                    if (status !== 'abort') {
                        console.error("Product category API error:", error);
                    }
                },
                complete: function() {
                    productCategoryApiRequest = null; // Reset request object
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
