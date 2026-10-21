<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companyPackingType();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_companyPackingType();
    });


    function fetch_companyPackingType() {
        console.log("search_by_packing_type L-3");
        let instance = $('.search_by_packing_type');
        let company_id = $(".search_by_company").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let packing_type_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }

        if (!packing_type_id) {
            packing_type_id = instance.attr("data-selectedPackingTyoeId");
        }

        if (company_id) {
            console.log("search_by_packing_type L-31");
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-packing-type',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Packing Type</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (packing_type_id == item.id) {
                                    options += "<option value='" + item.id + "' data-weight='" + item.weight + "' selected >" + item.name + " - " + item.weight + "</option>";
                                } else {
                                    options += "<option value='" + item.id + "' data-weight='" + item.weight + "'>" + item.name + " - " + item.weight + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_packing_type").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_packing_type").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_packing_type").attr('data-append')).empty();
                                $("." + $(".search_by_packing_type").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_packing_type").attr('data-append')).empty();
                                $("." + $(".search_by_packing_type").attr('data-append')).append(options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="packing_type_id" class="form-select search_by_packing_type';
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
                                '<div class="form-group"> <label for="packing_type_id">Select Packing Type';
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