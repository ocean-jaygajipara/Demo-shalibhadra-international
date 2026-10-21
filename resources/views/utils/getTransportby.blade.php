<script>
    if($('meta[name="company_id"]').attr('value')){
        fetch_transportList();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            fetch_transportList();
        }
    });

    function fetch_transportList(){
        let instance = $('.search_by_company');
        let company_id = instance.val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let transport_by_id = instance.attr("data-selectedTransportbyId");

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }

        if (!transport_by_id) {
            transport_by_id = $(".search_by_transport_by_id").attr("data-selectedTransportbyId");
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-transportby',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
                },
                data: {
                    company_id: company_id
                },
                success: function(response) {
                    if (response.status) {
                        // console.log("getTransportby 30",response.data, company_id, transport_by_id);
                        let options = "<option value=''>Select Transport By</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (transport_by_id == item.id) {
                                    options += "<option value='" + item.id +
                                    "' selected >" + item.name + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                    "'>" +
                                    item.name + "</option>";
                                }
                            });
                        }
                        // console.log("getTransportby 33",instance.parent(),);
                        if ($(".search_by_transport_by_id").attr('data-append')) {
                            $(".search_by_transport_by_id").attr('data-selectedCompanyId', company_id);
                            if ($(document).find("." + $(".search_by_transport_by_id").attr('data-append'))
                            .length > 0) {
                                $("." + $(".search_by_transport_by_id").attr('data-append')).empty();
                                $("." + $(".search_by_transport_by_id").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_transport_by_id").attr('data-append')).empty();
                                $("." + $(".search_by_transport_by_id").attr('data-append')).append(options);
                            }

                            if(transport_by_id != '') {
                                $('.search_by_transport_by_id').trigger("change");
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
                                        '<div class="form-group"> <label for="company_id">Select Transport By ';
                                            if (is_required) {
                                                html += '<span class="text-danger">*</span>';
                                            }
                                            html += '</label>';
                                            html += select_tag;
                                            html += '</div></div>';
                                            $(".company_div").remove();
                                            instance.parent().parent().after(html);
                                        } else if (instance.parent("tr")) {
                                            // console.log("getTransportby 39", instance, instance.parent(), instance.next("th"));
                                            $(".search_by_company").removeClass('d-none').empty().append(options);
                                            /*
                                            // $(".search_by_company").remove();
                                            // instance.parent().next("th").remove();
                                            // instance.parent().after('<th>'+options+'</th>');
                                            */
                                        } else {
                                            console.log("getTransportby 82", instance.parent());
                                            instance.parent().after(options);
                                        }
                                        // $('.search_by_transport_by_id').trigger("change");
                                        $('.select2').select2();
                                    }
                                }

                            });

                        }
                    }
                    $('.search_by_transport_by_id').trigger("change");
                </script>
