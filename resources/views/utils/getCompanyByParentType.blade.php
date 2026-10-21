<script>
    $(document).on('change', '.search_by_company', function() {
        let company_id = $(this).val();
        let parentTypeSelect = $('#parent_id');

        if (!company_id) {
            parentTypeSelect.html("<option value=''>-- Select Parent Type --</option>");
            return;
        }

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}customer/type/list',
            beforeSend: function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
            },
            data: {
                company_id: company_id
            },
            success: function(response) {
                if (response.status) {
                    let options = "<option value=''>-- Select Parent Type --</option>";
                    if (Array.isArray(response.data)) {
                        $.each(response.data, function(i, item) {
                            let selected =
                                "{{ old('parent_id', $edit->parent_id ?? '') }}" == item
                                .id ? "selected" : "";
                            options +=
                                `<option value="${item.id}" ${selected}>${item.name}</option>`;
                        });
                    }
                    parentTypeSelect.html(options).trigger("change");
                    if (parentTypeSelect.hasClass('select2')) {
                        parentTypeSelect.select2();
                    }
                }
            },
            error: function(xhr) {
                console.error('Error fetching customer types:', xhr.responseText);
            }
        });
    });

    $(document).ready(function() {
        if ($('.search_by_company').val()) {
            $('.search_by_company').trigger("change");
        }
    });
</script>
