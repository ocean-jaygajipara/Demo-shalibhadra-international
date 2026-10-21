<script>
    $(document).on('change', '.search_by_company', function() {
        console.log("Company changed");

        let company_id = $(this).val();
        let expense_category = $('#expense_category_id');
        let selectedCategoryId = expense_category.data("selectedcategoryid");

        if (!company_id) {
            expense_category.html("<option value=''>Select Expense Category</option>");
            $('#expense_subcategory_id').html("<option value=''>Select Expense SubCategory</option>");
            return;
        }
        

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}expense/category/list',
            beforeSend: function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
            },
            data: {
                company_id: company_id
            },
            success: function(response) {
                if (response.status) {
                    let options = "<option value=''>Select Expense Category</option>";
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            if (selectedCategoryId == item.id) {
                                options += "<option value='" + item.id + "' selected>" +
                                    item.name + "</option>";
                            } else {
                                options += "<option value='" + item.id + "'>" + item.name +
                                    "</option>";
                            }
                        });
                    }
                    expense_category.html(options);

                    if (selectedCategoryId) {
                        expense_category.val(selectedCategoryId).trigger("change");
                    }

                    if (expense_category.hasClass('select2')) {
                        expense_category.select2();
                    }
                }
            }

        });
    });

    $(document).on('change', '#expense_category_id', function() {
        console.log("Expense Category changed");
        let expense_category_id = $(this).val();
        let company_id = $('.search_by_company').val();
        let expense_subcategory = $('#expense_subcategory_id');
        let selectedSubCategoryId = expense_subcategory.data("selectedsubcategoryid");

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!expense_category_id) {
            expense_subcategory.html("<option value=''>Select Expense SubCategory</option>");
            return;
        }

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}expense/sub-category/list',
            beforeSend: function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
            },
            data: {
                expense_category_id: expense_category_id,
                company_id: company_id
            },
            success: function(response) {
                if (response.status) {
                    let options = "<option value=''>Select Expense SubCategory</option>";
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            if (selectedSubCategoryId == item.id) {
                                options += "<option value='" + item.id + "' selected>" +
                                    item.name + "</option>";
                            } else {
                                options += "<option value='" + item.id + "'>" + item.name +
                                    "</option>";
                            }
                        });
                    }
                    expense_subcategory.html(options);
                    filter_expanse_category = $(
                        'select[name="expense_category_id"] option:selected').val();
                    if ($.trim(filter_expanse_category || '') !== '') {
                        expense_subcategory.trigger("change");
                    }


                    if (expense_subcategory.hasClass('select2')) {
                        expense_subcategory.select2();
                    }
                }
            }
        });
    });

    // Trigger initial load
    $('.search_by_company').trigger("change");
</script>
