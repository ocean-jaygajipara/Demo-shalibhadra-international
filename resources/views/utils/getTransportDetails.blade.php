<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_transportDetailList();
    }

    $(document).on('change', '.search_by_transport_by_id', function() {
        fetch_transportDetailList();
    });

    function fetch_transportDetailList() {

        let company_id = $('.search_by_company').val();
        let transport_by_id = $('.search_by_transport_by_id').val();
        let instance = $('.search_by_transport_details_id');
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let transport_details_id = instance.attr("data-selectedTransportdetailsId");
        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }


        if (!transport_details_id) {
            transport_details_id = $(".search_by_transport_details_id").attr("data-selectedTransportdetailsId");
        }

        if (company_id && transport_by_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-transport-details',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                    transport_by_id: transport_by_id
                },
                success: function(response) {
                    if (response.status) {
                        // console.log("getTransportby 30",response.data, company_id, transport_by_id);
                        let options = "<option value=''>Select Transport Details</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (transport_details_id == item.id) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.transport_details + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.transport_details + "</option>";
                                }
                            });
                        }
                        console.log(options);
                        // console.log("getTransportby 33",instance.parent(),);
                        if ($(".search_by_transport_details_id").attr('data-append')) {
                            $(".search_by_transport_details_id").attr('data-selectedTransportdetailsId',
                                transport_details_id);
                            if ($(document).find("." + $(".search_by_transport_details_id").attr(
                                    'data-append'))
                                .length > 0) {
                                $("." + $(".search_by_transport_details_id").attr('data-append')).empty();
                                $("." + $(".search_by_transport_details_id").attr('data-append')).append(
                                    options);
                            } else {
                                $("." + $(".search_by_transport_details_id").attr('data-append')).empty();
                                $("." + $(".search_by_transport_details_id").attr('data-append')).append(
                                    options);
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
                                '<div class="form-group"> <label for="transport_details_id">Select Transport Details ';
                            if (is_required) {
                                html += '<span class="text-danger">*</span>';
                            }
                            html += '</label>';
                            html += select_tag;
                            html += '</div></div>';
                            $(".company_div").remove();
                            instance.parent().parent().after(html);
                        } else if (instance.parent("tr")) {
                            // console.log("getTransportDetails 39", instance, instance.parent(), instance.next("th"));
                            $(".search_by_company").removeClass('d-none').empty().append(options);
                            /*
                            // $(".search_by_company").remove();
                            // instance.parent().next("th").remove();
                            // instance.parent().after('<th>'+options+'</th>');
                            */
                        } else {
                            console.log("getTransportDetails 82", instance.parent());
                            instance.parent().after(options);
                        }
                        // $('.search_by_transport_details_id').trigger("change");
                        $('.select2').select2();
                    }
                }
            });
        }else{
            let options = "<option value=''>Select Transport Details</option>";
            $("." + $(".search_by_transport_details_id").attr('data-append')).html(
                                    options);
        }
    }
    $('.search_by_transport_by_id').trigger("change");
</script>
