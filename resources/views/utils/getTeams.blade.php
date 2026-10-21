<script>
    let teamApiRequest = null; // Declare globally, outside the event

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_teams();
    } else {
        $(document).on('change', '.search_by_company', function() {
            fetch_teams();
        });
    }

    function fetch_teams() {
        let edit_id = $('#edit_id').val();
        let instance1 = $('.inquiry_created_by');
        let instance2 = $('.inquiry_assigned_to');
        let company_id = $(".search_by_company option:selected").val();

        let is_required = instance1.attr('required');
        let is_select2 = instance1.hasClass('select2');

        let is_required2 = instance2.attr('required');
        let is_select22 = instance2.hasClass('select2');

        let created_by_id = '';
        let assigned_to_id = '';
        let task_created_by = '';
        let task_assigned_to = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            company_id = $(".search_by_company").attr("data-selectedCompanyId");
        }

        if (!created_by_id) {
            created_by_id = $('.search_by_created_by').attr("data-selectedinquirycreatedby");
        }

        if (!assigned_to_id) {
            assigned_to_id = $('.search_by_assigned_to').attr("data-selectedinquiryassignedto");
            selectedIds = assigned_to_id ? assigned_to_id.toString().split(',') : [];
        }

        if (!task_created_by) {
            task_created_by = $('.search_by_created_by').attr("data-selectedTaskCreatedBy");
        }

        if (!task_assigned_to) {
            task_assigned_to = $('.search_by_assigned_to').attr("data-selectedTaskAssignedTo");
            selectedAssignIds = task_assigned_to ? task_assigned_to.toString().split(',') : [];
        }

        if (company_id) {
            // Abort previous request if any
            if (teamApiRequest !== null) {
                teamApiRequest.abort();
            }

            teamApiRequest = $.ajax({
                    type: 'POST',
                    url: '{{ env('API_URL') }}get-teams',
                    'beforeSend': function(request) {
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                            'content'));
                    },
                    data: {
                        company_id: company_id,
                    },
                    success: function(response) {
                        if (response.status) {
                            let options = "<option value=''>Select Team Person</option>";
                            let options2 = "<option value=''>Select Team Person</option>";
                            if (response.data && response.data.length > 0) {
                                $.each(response.data, function(i, item) {
                                    if (edit_id && edit_id == item.id) {
                                        return true;
                                    }

                                    // options for created_by_id / task_created_by
                                    if (created_by_id == item.id || task_created_by == item.id) {
                                        options += "<option value='" + item.id + "' selected>" + item.team_name + "</option>";
                                    } else {
                                        options += "<option value='" + item.id + "'>" + item.team_name + "</option>";
                                    }

                                    // options2: check if item is in either selectedIds OR selectedAssignIds
                                    if (selectedIds.includes(item.id.toString()) || selectedAssignIds.includes(item.id.toString())) {
                                        options2 += "<option value='" + item.id + "' selected>" + item.team_name + "</option>";
                                    } else {
                                        options2 += "<option value='" + item.id + "'>" + item.team_name + "</option>";
                                    }
                                });
                            }

                            if ($(".search_by_created_by").attr('data-append') || $(".search_by_assigned_to")
                                .attr('data-append')) {
                                if ($(document).find("." + $(".search_by_created_by").attr('data-append'))
                                    .length > 0) {
                                    $("." + $(".search_by_created_by").attr('data-append')).empty();
                                    $("." + $(".search_by_created_by").attr('data-append')).append(options);
                                } else {
                                    $("." + $(".search_by_created_by").attr('data-append')).empty();
                                    $("." + $(".search_by_created_by").attr('data-append')).append(options);
                                }

                                if ($(document).find("." + $(".search_by_assigned_to").attr('data-append'))) {
                                    if ($(document).find("." + $(".search_by_assigned_to").attr('data-append'))
                                        .length > 0) {
                                        $("." + $(".search_by_assigned_to").attr('data-append')).empty();
                                        $("." + $(".search_by_assigned_to").attr('data-append')).append(
                                            options2);
                                    } else {
                                        $("." + $(".search_by_assigned_to").attr('data-append')).empty();
                                        $("." + $(".search_by_assigned_to").attr('data-append')).append(
                                            options2);
                                    }
                                }
                            } else if (instance1.parent().parent().hasClass("col-md-6") || instance1
                                .parent().parent().hasClass("col-md-4") || instance1.parent().parent()
                                .hasClass("col-md-3")) {

                                let select_tag =
                                    '<select name="created_by_id" class="form-select search_by_created_by';
                                if (is_select2) {
                                    select_tag += ' select2 ';
                                }
                                select_tag += '"';
                                if (is_required) {
                                    select_tag += ' required ';
                                }
                                select_tag += '>' + options + '</select>';


                                let html = '';
                                if (instance1.parent().parent().hasClass("col-md-4")) {
                                    html = '<div class="col-md-4 state_div">';
                                } else if (instance1.parent().parent().hasClass("col-md-3")) {
                                    html = '<div class="col-md-3 state_div">';
                                } else {
                                    html = '<div class="col-md-6 state_div">';
                                }
                                html +=
                                    '<div class="form-group"> <label for="state_id">Select City ';
                                if (is_required) {
                                    html += '<span class="text-danger">*</span>';
                                }
                                html += '</label>';
                                html += select_tag;
                                html += '</div></div>';
                                $(".country_div").remove();
                                instance1.parent().parent().after(html);
                            } else if (instance1.parent("tr")) {
                                // console.log("getState 39", instance, instance.parent(), instance.next("th"));
                                $(".search_by_area").removeClass('d-none').empty().append(options);
                                /*
                                // $(".search_by_country").remove();
                                // instance.parent().next("th").remove();
                                // instance.parent().after('<th>'+options+'</th>');
                                */
                            } else {
                                console.log("getState 82", instance1.parent());
                                instance1.parent().after(options);
                            }
                            // $('.search_by_state').trigger("change");
                            $('.select2').select2();
                        }
                    }
                });
        }
    }
    $('.search_by_company').trigger("change");
</script>
