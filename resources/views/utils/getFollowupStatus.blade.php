<script>
    if($('meta[name="company_id"]').attr('value')){
        fetch_followupStatus();
    }else{
        $(document).on('change', '.search_by_company', function() {
            fetch_followupStatus();
        });
    }



    function fetch_followupStatus(){
        let instance = $('.search_by_followup_status');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let followup_status_id = '';
        let setIsDefault = instance.attr('data-setIsDefault');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }

        if (!followup_status_id) {
            followup_status_id = instance.attr("data-selectedFollowupStatusId");
        }
        if (company_id) {

            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-followup-status',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Followup Status</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (followup_status_id == item.id) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.followup_status + "</option>";
                                } else if(!followup_status_id && setIsDefault && setIsDefault == "yes" && item.is_default == "yes") {
                                    options += "<option value='" + item.id +
                                    "' selected >" + item.followup_status + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.followup_status + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_followup_status").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_followup_status").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_followup_status").attr('data-append')).empty();
                                $("." + $(".search_by_followup_status").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_followup_status").attr('data-append')).empty();
                                $("." + $(".search_by_followup_status").attr('data-append')).append(options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="designation_id" class="form-select search_by_followup_status';
                            if (is_select2) {
                                select_tag += ' select2 ';
                            }
                            select_tag += '"';
                            if (is_required) {
                                select_tag += ' required ';
                            }
                            select_tag += '>' + options + '</select>';


                            let html = '';
                            if (instance.parent().parent().hasClass("col-md-4")) {
                                html = '<div class="col-md-4 state_div">';
                            } else if (instance.parent().parent().hasClass("col-md-3")) {
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
                            instance.parent().parent().after(html);
                        } else if (instance.parent("tr")) {
                            // console.log("getState 39", instance, instance.parent(), instance.next("th"));
                            $(".search_by_area").removeClass('d-none').empty().append(options);
                            /*
                            // $(".search_by_country").remove();
                            // instance.parent().next("th").remove();
                            // instance.parent().after('<th>'+options+'</th>');
                            */
                        } else {
                            console.log("getState 82", instance.parent());
                            instance.parent().after(options);
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
