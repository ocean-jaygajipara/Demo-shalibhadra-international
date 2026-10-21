<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_designation();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            fetch_designation();
        }
    });

    function fetch_designation() {
        let company_id = $(".search_by_company option:selected").val();
        const instance = $('.search_by_designation');
        const designation_id = instance.data("selecteddesignationid") || '';
        const is_required = instance.attr('required');
        const is_select2 = instance.hasClass('select2');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-designation',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { company_id },
                success: function(response) {
                    if (response.status && Array.isArray(response.data)) {
                        let options = "<option value=''>Select Designation</option>";
                        $.each(response.data, function(i, item) {
                            let selected = designation_id == item.id ? "selected" : "";
                            options += `<option value="${item.id}" ${selected}>${item.designation_name}</option>`;
                        });

                        const appendTarget = instance.data('append');
                        if (appendTarget && $("." + appendTarget).length) {
                            $("." + appendTarget).html(options);
                        } else {
                            const container = instance.closest('.col-md-6, .col-md-4, .col-md-3');
                            if (container.length) {
                                const colClass = container.attr('class').split(' ').find(cls => cls.startsWith('col-md-'));
                                const selectTag = `<select name="designation_id" class="form-select search_by_designation${is_select2 ? ' select2' : ''}" ${is_required ? 'required' : ''}>${options}</select>`;
                                const html = `<div class="${colClass} state_div">
                                    <div class="form-group">
                                        <label for="designation_id">Select Designation ${is_required ? '<span class="text-danger">*</span>' : ''}</label>
                                        ${selectTag}
                                    </div>
                                </div>`;
                                $(".country_div").remove();
                                container.after(html);
                            } else {
                                instance.after(options);
                            }
                        }

                        $('.select2').select2();
                    }
                },
                error: function(err) {
                    console.error("Designation fetch failed", err);
                }
            });
        }
    }
</script>
