
<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_sourceOfInquiry();
    }else{
        $(document).on('change', '.search_by_company', function() {
            fetch_sourceOfInquiry();
        });
    }


    function fetch_sourceOfInquiry() {
        let instance = $('.search_by_source_of_inquiry');
        let instance2 = $('.search_by_source_of_complaint');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let source_of_inquiry_id = '';
        let source_of_complaint_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!source_of_inquiry_id) {
            source_of_inquiry_id = instance.attr("data-selectedSourceOfInquiryId");
        }

        if (!source_of_complaint_id) {
            source_of_complaint_id = $('.search_by_source_of_complaint').attr("data-selectedSourceOfComplaintId");
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-source-of-inquiry',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Source of Inquiry</option>";
                        let options2 = "<option value=''>Select Source Of Complaint</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (source_of_inquiry_id == item.id) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.source_of_inquiry + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.source_of_inquiry + "</option>";
                                }

                                if (source_of_complaint_id == item.id) {
                                    options2 += "<option value='" + item.id +
                                        "' selected >" + item.source_of_inquiry + "</option>";
                                } else {
                                    options2 += "<option value='" + item.id +
                                        "'>" +
                                        item.source_of_inquiry + "</option>";
                                }

                            });
                        }
                        if ($(".search_by_source_of_inquiry").attr('data-append') || $(".search_by_source_of_complaint").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_source_of_inquiry").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_source_of_inquiry").attr('data-append')).empty();
                                $("." + $(".search_by_source_of_inquiry").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_source_of_inquiry").attr('data-append')).empty();
                                $("." + $(".search_by_source_of_inquiry").attr('data-append')).append(options);
                            }

                            if ($("." + $(".search_by_source_of_complaint").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_source_of_complaint").attr('data-append')).empty();
                                $("." + $(".search_by_source_of_complaint").attr('data-append')).append(options2);
                            } else {
                                $("." + $(".search_by_source_of_complaint").attr('data-append')).empty();
                                $("." + $(".search_by_source_of_complaint").attr('data-append')).append(options2);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="source_of_inquiry_id" class="form-select search_by_source_of_inquiry';
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
                                '<div class="form-group"> <label for="source_of_inquiry_id">Select Source of Inquiry ';
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
                            $(".search_by_source_of_complaint").removeClass('d-none').empty().append(options2);
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
