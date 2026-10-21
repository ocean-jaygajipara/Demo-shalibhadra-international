<script>
    let productBOMCategoryApiRequest = null;
    let productBOMCategoryApiLoading = false;

    if ($('meta[name="company_id"]').attr('value')) {
        filter_company = $('meta[name="company_id"]').attr('value');
        if ($.trim(filter_company || '') !== '') {
            fetchProductBOMCategory();
        }
    }

    $(document).on('change', '.search_by_company', function() {
        filter_company = $('select[name="company_id"] option:selected').val();
        if ($.trim(filter_company || '') !== '') {
            fetchProductBOMCategory();
        }
    });

    function fetchProductBOMCategory() {
        // Prevent duplicate calls if request is already in progress
        if (productBOMCategoryApiLoading) {
            console.log("Product category API request already in progress, skipping duplicate call");
            return;
        }

        // console.log("product category L-3");
        let instance = $('.search_by_product_bom_category');
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
            if (productBOMCategoryApiRequest !== null) {
                productBOMCategoryApiRequest.abort();
            }
            productBOMCategoryApiLoading = true; // Set loading flag
            productBOMCategoryApiRequest = $.ajax({
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
                    productBOMCategoryApiLoading = false; // Reset loading flag on success
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
                        if ($(".search_by_product_bom_category").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_product_bom_category").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_product_bom_category").attr('data-append')).empty();
                                $("." + $(".search_by_product_bom_category").attr('data-append')).append(
                                    options);
                            } else {
                                $("." + $(".search_by_product_bom_category").attr('data-append')).empty();
                                $("." + $(".search_by_product_bom_category").attr('data-append')).append(
                                    options);
                            }

                            filter_product_category = $(
                                'select[name="bom_product_category_id"] option:selected').val();
                            if ($.trim(filter_product_category || '') !== '') {
                                $('.search_by_product_bom_category').trigger("change");
                            }
                        }

                        $('#bom_product_category_id').select2({
                            dropdownParent: $('#product_bom_modal')
                        });
                    }
                },
                error: function(xhr, status, error) {
                    productBOMCategoryApiLoading = false; // Reset loading flag on error
                    if (status !== 'abort') {
                        console.error("Product category API error:", error);
                    }
                },
                complete: function() {
                    productBOMCategoryApiRequest = null; // Reset request object
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
