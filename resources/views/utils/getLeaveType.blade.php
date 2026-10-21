<script>
    $(document).ready(function () {
        if ($('meta[name="company_id"]').attr('value')) {
            console.log("L-4");

            fetch_leaveType();
        }

        $(document).on('change', '.search_by_company', function () {
            console.log("L-10");
            fetch_leaveType();
        });

        function fetch_leaveType() {
            let company_id = $('.search_by_company').val();
            let leaveTypeSelect = $('#filter_leave_type');

            if (!company_id && $('meta[name="company_id"]').attr('value')) {
                company_id = $('meta[name="company_id"]').attr('value');
            }

            if (!company_id) {
                leaveTypeSelect.html("<option value=''>Select Leave Type</option>");
                return;
            }

            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}leave/type/list',
                beforeSend: function (request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: { company_id },
                success: function (response) {
                    if (response.status) {
                        let options = "<option value=''>Select Leave Type</option>";
                        leaveTypeSelect.html("<option value=''>Select Leave Type</option>");
                        if (Array.isArray(response.data)) {
                            $.each(response.data, function (i, item) {
                                let selected = "{{ old('leave_type_id', $edit->leave_type_id ?? '') }}" == item.id ? "selected" : "";
                                options += `<option value="${item.id}" ${selected}>${item.name}</option>`;
                            });
                        }
                        leaveTypeSelect.html(options).trigger("change");
                        if (leaveTypeSelect.hasClass('select2')) {
                            leaveTypeSelect.select2();
                        }
                    }
                }
            });
        }

        $('.search_by_company').trigger("change");
    });
</script>
