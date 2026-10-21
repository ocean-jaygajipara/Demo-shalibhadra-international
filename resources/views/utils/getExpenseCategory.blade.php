<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_expenseCategory();
    }
    $(document).on('change', '.team_person_select', function() {
        filter_company = $('meta[name="company_id"]').attr('value');
        if ($.trim(filter_company || '') !== '') {
            fetch_expenseCategory();
        }
    });



    $(document).on('change', '.search_by_company', function() {
        fetch_expenseCategory();

    });

    function fetch_expenseCategory() {

        let instance = $('.search_by_company');
        let company_id = instance.val();
        let category_select = $('#expense_category_id');
        let selectedCategoryId = category_select.data("selectedcategoryid");

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            category_select.html("<option value=''>Select Expense Category</option>");
            return;
        }

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}expense/category/list',
            beforeSend: function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
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
                                options += "<option value='" + item.id + "' selected>" + item.name +
                                    "</option>";
                            } else {
                                options += "<option value='" + item.id + "'>" + item.name +
                                    "</option>";
                            }
                        });
                    }

                    category_select.html(options);
                    filter_company = $('select[name="company_id"] option:selected').val();
                    if ($.trim(filter_company || '') !== '') {
                        category_select.trigger("change");
                    }

                    if (category_select.hasClass('select2')) {
                        category_select.select2();
                    }
                }
            }
        });
    }

    //$('.search_by_company').trigger("change");
</script>
