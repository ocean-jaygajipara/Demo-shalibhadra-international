<script>
$(document).on('change', '.search_by_company', function() {
    let company_id = $(this).val();

    let personSelect = $('#team_person_ids');
    let selectedIdsStr = personSelect.data('selectedids');
    let selectedIds = selectedIdsStr ? selectedIdsStr.toString().split(',') : [];
    let parent_type_id = $(this).attr('data-parent_type_id') || 'all';

    if (!company_id && $('meta[name="company_id"]').attr('value')) {
        company_id = $('meta[name="company_id"]').attr('value');
    }

    if (!company_id) {
        personSelect.html("<option value=''>Select Team Person</option>");
        $('#employee_code').val('');
        return;
    }

    $.ajax({
        type: 'POST',
        url: '{{ env('API_URL') }}team-person/list',
        beforeSend: function(request) {
            request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
        },
        data: {
            company_id: company_id,
            parent_type_id: parent_type_id
        },
        success: function(response) {
            if (response.status) {
                let options = "<option value=''>Select Team Person</option>";
                if (Array.isArray(response.data)) {
                    $.each(response.data, function(i, item) {
                        let selected = selectedIds.includes(item.id.toString()) ? "selected" : "";
                        options += `<option value="${item.id}" data-empcode="${item.employee_code}" ${selected}>${item.name}</option>`;
                    });
                }
                personSelect.html(options).trigger("change");

                if (personSelect.hasClass('select2')) {
                    personSelect.select2();
                }
            }
        }
    });

    $.ajax({
        type: 'POST',
        url: '{{ env('API_URL') }}generate-employee-code',
        beforeSend: function(request) {
            request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
        },
        data: {
            company_id: company_id,
        },
        success: function(response) {
            console.log("Employee code response:", response);
            if (response.status && response.data) {
                setTimeout(function() {
                    $('#employee_code').val(response.data);
                }, 100);
            } else {
                $('#employee_code').val('');
            }
        }
    });
});

$('.search_by_company').trigger("change");
</script>
