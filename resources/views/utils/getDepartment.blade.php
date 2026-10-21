<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_department();
    }

    $(document).on('change', '.search_by_company', function () {
        if ($(".search_by_company option:selected").val()) {
            fetch_department();
        }
    });

    function fetch_department() {
        let company_id = $(".search_by_company option:selected").val();
        const instance = $('.search_by_department');
        const selected_id = instance.data("selecteddepartmentid") || '';
        const is_required = instance.attr('required');
        const is_select2 = instance.hasClass('select2');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env("API_URL") }}get-department',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { company_id },
                success: function (response) {
                    if (response.status && Array.isArray(response.data)) {
                        let options = "<option value=''>Select Department</option>";
                        $.each(response.data, function (i, item) {
                            const selected = selected_id == item.id ? "selected" : "";
                            options += `<option value="${item.id}" ${selected}>${item.department_name}</option>`;
                        });

                        instance.html(options);
                        if (is_select2) {
                            instance.select2();
                        }
                    }
                },
                error: function (err) {
                    console.error("Department fetch failed", err);
                }
            });
        }
    }
</script>
