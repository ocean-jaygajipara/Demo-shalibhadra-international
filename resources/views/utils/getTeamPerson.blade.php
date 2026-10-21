<script>
    let teamPersonFetchRequest = null; // Declare globally, outside the event

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_teamPerson();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_teamPerson();
    });

    // Initial trigger
    $('.search_by_company').trigger("change");

    function fetch_teamPerson() {
        let company_id = $('.search_by_company').val();
        let personSelect = $('#team_person_ids');
        let selectedIdsStr = personSelect.data('selectedids');
        let selectedIds = selectedIdsStr ? selectedIdsStr.toString().split(',') : [];
        let parent_type_id = $(this).attr('data-parent_type_id');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            personSelect.html("<option value=''>Select Team Person</option>");
            return;
        }

        let filterData = {
            company_id: company_id,
            parent_type_id: parent_type_id || 'all'
        };
        console.log("filterData 35", Object.keys(filterData).length);

        if(Object.keys(filterData).length > 0){

            // Abort previous request if any
            if (teamPersonFetchRequest !== null) {
                teamPersonFetchRequest.abort();
            }

            teamPersonFetchRequest = $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}team-person/list',
                beforeSend: function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: filterData,
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Team Person</option>";
                        if (Array.isArray(response.data)) {
                            $.each(response.data, function(i, item) {
                                let selected = selectedIds.includes(item.id.toString()) ? "selected" :
                                    "";
                                options +=
                                    `<option value="${item.id}" data-empcode="${item.employee_code}" ${selected}>${item.name}</option>`;
                            });
                        }
                        personSelect.html(options).trigger("change");

                        if (personSelect.hasClass('select2')) {
                            personSelect.select2();
                        }
                    }
                }
            });
        }
    }
</script>
