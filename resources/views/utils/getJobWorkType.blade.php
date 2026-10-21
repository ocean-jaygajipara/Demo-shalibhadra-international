<script>
    if($('meta[name="company_id"]').attr('value')){
        fetch_jobworktype();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            fetch_jobworktype();
        }
    });

    function fetch_jobworktype(){
        let instance = $('.search_by_company');
        let company_id = instance.val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let job_work_type_id = instance.attr("data-selectedJobworkTypeId");

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }

        if (!job_work_type_id) {
            job_work_type_id = $(".search_by_job_work_type_id").attr("data-selectedJobworkTypeId");
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-job-work-type',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
                },
                data: {
                    company_id: company_id
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Job work Type</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (job_work_type_id == item.id) {
                                    options += "<option data-bl_percentage='"+item.bl_percentage+"' data-bl_checkbox='"+item.bl_checkbox+"' value='" + item.id +
                                    "' selected >" + item.name + "</option>";
                                } else {
                                    options += "<option data-bl_percentage='"+item.bl_percentage+"' data-bl_checkbox='"+item.bl_checkbox+"' value='" + item.id +
                                    "'>" +
                                    item.name + "</option>";
                                }
                            });
                        }


                        if ($(".search_by_job_work_type_id").attr('data-append')) {
                            $(".search_by_job_work_type_id").attr('data-selectedCompanyId', company_id);
                            if ($(document).find("." + $(".search_by_job_work_type_id").attr('data-append'))
                            .length > 0) {
                                $("." + $(".search_by_job_work_type_id").attr('data-append')).empty();
                                $("." + $(".search_by_job_work_type_id").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_job_work_type_id").attr('data-append')).empty();
                                $("." + $(".search_by_job_work_type_id").attr('data-append')).append(options);
                            }

                            if(job_work_type_id != '') {
                                $('.search_by_job_work_type_id').trigger("change");
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                        .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                        .hasClass("col-md-3")) {

                            let select_tag =
                            '<select name="company_id" class="form-select search_by_company';
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
                                html = '<div class="col-md-4 company_div">';
                                } else if (instance.parent().parent().hasClass("col-md-3")) {
                                    html = '<div class="col-md-3 company_div">';
                                    } else {
                                        html = '<div class="col-md-6 company_div">';
                                        }
                                        html +=
                                        '<div class="form-group"> <label for="company_id">Select Job work Type ';
                                            if (is_required) {
                                                html += '<span class="text-danger">*</span>';
                                            }
                                            html += '</label>';
                                            html += select_tag;
                                            html += '</div></div>';
                                            $(".company_div").remove();
                                            instance.parent().parent().after(html);
                                        } else if (instance.parent("tr")) {
                                            $(".search_by_company").removeClass('d-none').empty().append(options);

                                        } else {
                                            instance.parent().after(options);
                                        }
                                        // $('.search_by_job_work_type_id').trigger("change");
                                        $('.select2').select2();
                                    }
                                }

                            });

                        }
                    }
                    $('.search_by_job_work_type_id').trigger("change");
                </script>
