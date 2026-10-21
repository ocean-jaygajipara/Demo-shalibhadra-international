<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_visitor_type();
    }

    $(document).on('change', '.search_by_company', function () {
        if ($(".search_by_company option:selected").val()) {
            fetch_visitor_type();
        }
    });

    function fetch_visitor_type() {
        let company_id = $(".search_by_company option:selected").val();
        const instance = $('.search_by_visitor_type');
        const selected_id = instance.data("selectedvisitortypeid") || '';
        const is_required = instance.attr('required');
        const is_select2 = instance.hasClass('select2');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env("API_URL") }}get-visitor-type',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { company_id },
                success: function (response) {
                    if (response.status && Array.isArray(response.data)) {
                        let options = "<option value=''>Select Visitor Type</option>";
                        $.each(response.data, function (i, item) {
                            const selected = selected_id == item.id ? "selected" : "";
                            options += `<option value="${item.id}" ${selected}>${item.visitor_type_name}</option>`;
                        });

                        instance.html(options);
                        if (is_select2) {
                            instance.select2();
                        }
                    }
                },
                error: function (err) {
                    console.error("Visitor Type fetch failed", err);
                }
            });
        }
    }
</script>
