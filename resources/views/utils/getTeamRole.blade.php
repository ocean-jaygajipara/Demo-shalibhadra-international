<script>
    if ($('meta[name="company_id"]').attr('value')) {
        //fetch_teamPerson();
        fetch_teamRole();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            //fetch_teamPerson();
            fetch_teamRole();
        }
    });

    function fetch_teamRole() {
        let instance = $('.search_by_team_role');
        let company_id = $(".search_by_company option:selected").val();

        let team_role_id = instance.data('selectedroleid') || '';
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-team-role',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    company_id
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Team Role</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                let selected = (team_role_id == item.id) ? "selected" : "";
                                options +=
                                    `<option value="${item.id}" ${selected}>${item.team_role_name}</option>`;
                            });
                        }

                        let appendTarget = instance.data('append');

                        if (appendTarget && $("." + appendTarget).length) {
                            $("." + appendTarget).empty().append(options);
                        } else {
                            let container = instance.closest('.col-md-6, .col-md-4, .col-md-3');
                            if (container.length) {
                                let colClass = container.attr('class').split(' ').find(cls => cls.startsWith(
                                    'col-md-')) || 'col-md-6';

                                let selectTag =
                                    `<select name="team_role_id" class="form-select search_by_team_role${is_select2 ? ' select2' : ''}"${is_required ? ' required' : ''}>${options}</select>`;
                                let html = `<div class="${colClass} state_div">
                                            <div class="form-group">
                                                <label for="team_role_id">Select Team Role ${is_required ? '<span class="text-danger">*</span>' : ''}</label>
                                                ${selectTag}
                                            </div>
                                        </div>`;

                                $(".country_div").remove();
                                container.after(html);
                            } else if (instance.parent("tr").length) {
                                $(".search_by_area").removeClass('d-none').empty().append(options);
                                instance.parent().parent().after(html);
                            } else if (instance.parent("tr")) {
                                // console.log("getState 39", instance, instance.parent(), instance.next("th"));
                                $(".search_by_area, .search_by_team_role").removeClass('d-none').empty().append(options);
                                /*
                                // $(".search_by_country").remove();
                                // instance.parent().next("th").remove();
                                // instance.parent().after('<th>'+options+'</th>');
                                */
                            } else {
                                instance.empty().append(options);
                            }
                        }

                        if (is_select2) {
                            $('.search_by_team_role').select2();
                        }
                    }
                },
                error: function(err) {
                    console.error("Failed to fetch team roles", err);
                }
            });
        }
    }

    // function fetch_teamPerson() {
    //     let company_id = $(".search_by_company option:selected").val();
    //     const personSelect = $('#team_person_ids');
    //     const selectedIdsStr = personSelect.data('selectedids');
    //     const selectedIds = selectedIdsStr ? selectedIdsStr.toString().split(',') : [];

    //     let parent_type_id = $('.search_by_company').data('parent_type_id') || 'all';

    //     if (!company_id && $('meta[name="company_id"]').attr('value')) {
    //         company_id = $('meta[name="company_id"]').attr('value');
    //     }

    //     if (!company_id) {
    //         personSelect.html("<option value=''>Select Team Person</option>");
    //         return;
    //     }

    //     if (company_id) {
    //          $.ajax({
    //             type: 'POST',
    //             url: '{{ env('API_URL') }}team-person/list',
    //             headers: {
    //                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //             },
    //             data: {
    //                 company_id: company_id,
    //                 parent_type_id: parent_type_id
    //             },
    //             success: function(response) {
    //                 if (response.status && Array.isArray(response.data)) {
    //                     let options = "<option value=''>Select Team Person</option>";
    //                     $.each(response.data, function(i, item) {
    //                         let selected = selectedIds.includes(item.id.toString()) ? "selected" : "";
    //                         options += `<option value="${item.id}" ${selected}>${item.name}</option>`;
    //                     });
    //                     personSelect.html(options).trigger("change");

    //                     if (personSelect.hasClass('select2')) {
    //                         personSelect.select2();
    //                     }
    //                 }
    //             },
    //             error: function(err) {
    //                 console.error("Team person fetch failed", err);
    //             }
    //         });
    //     }
    // }
</script>
