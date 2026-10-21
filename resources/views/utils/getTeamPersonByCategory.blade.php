<script>
    $(document).on('change', '#team_person_ids', function() {
        let team_person_id = $(this).val();
        let company_id = $('#company_id').val();
        let expenseCategorySelect = $('#expense_category_id');
        let subCategorySelect = $('#expense_subcategory_id');

        subCategorySelect.html("<option value=''>Select Expense SubCategory</option>");

        let selectedCategoryIdStr = expenseCategorySelect.data('selectedcategoryid');
        let selectedCategoryId = selectedCategoryIdStr ? selectedCategoryIdStr.toString() : "";

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        
        if (!team_person_id || !company_id) {
            expenseCategorySelect.html("<option value=''>Select Expense Category</option>");
            return;
        }

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}expense/category/list',
            data: {
                team_person_id: team_person_id,
                company_id: company_id
            },
            beforeSend: function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
            },
            success: function(response) {
                if (response.status && Array.isArray(response.data)) {
                    let options = "<option value=''>Select Expense Category</option>";
                    $.each(response.data, function(i, item) {
                        let selected = (selectedCategoryId === item.id.toString()) ?
                            "selected" : "";
                        options +=
                            `<option value="${item.id}" ${selected}>${item.name}</option>`;
                    });

                    expenseCategorySelect.html(options).trigger("change");

                    if (expenseCategorySelect.hasClass('select2')) {
                        expenseCategorySelect.select2();
                    }
                }
            }
        });
    });

    $(document).on('change', '#expense_category_id', function() {
        let category_id = $(this).val();
        let company_id = $('#company_id').val();
        let team_person_id = $('#team_person_ids').val(); // Fetching value
        let subCategorySelect = $('#expense_subcategory_id');
        let selectedSubCategoryIdStr = subCategorySelect.data('selectedsubcategoryid');
        let selectedSubCategoryId = selectedSubCategoryIdStr ? selectedSubCategoryIdStr.toString() : "";

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!category_id || !company_id) {
            subCategorySelect.html("<option value=''>Select Expense SubCategory</option>");
            return;
        }

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}expense/sub-category/list',
            data: {
                expense_category_id: category_id,
                company_id: company_id,
                team_person_id: team_person_id // Add here
            },
            beforeSend: function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
                $("#global-loader").show();
            },
            success: function(response) {

                if (response.status) {
                    $("#global-loader").hide();
                    let options = "<option value=''>Select Expense SubCategory</option>";
                    $.each(response.data, function(i, item) {
                        // let selected = (selectedSubCategoryId === item.id.toString()) ? "selected" : "";
                        // // options += `<option value="${item.id}" ${selected} data-categoryData='${JSON.stringify(item)}'>${item.name}</option>`;
                        let tempOption = "<option value='" + item.id + "' ";
                        if (selectedSubCategoryId === item.id.toString()) {
                            tempOption += " selected ";
                        }
                        tempOption += " data-categoryData='" + JSON.stringify(item) + "'>";
                        tempOption += item.name;
                        tempOption += "</option>";
                        options += tempOption;
                    });

                    subCategorySelect.html(options);
                    // subCategorySelect.trigger("change");

                    if (subCategorySelect.hasClass('select2')) {
                        subCategorySelect.select2();
                    }
                }
            },
        });
    });


    // Initial trigger on page load
    $('#team_person_ids').trigger("change");
</script>
