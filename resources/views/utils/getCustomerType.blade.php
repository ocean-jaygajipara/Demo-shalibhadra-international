<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companyCustomerType();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_companyCustomerType();
    });

    function fetch_companyCustomerType() {
        console.log("search_by_customer_type L-3");
            let company_id = $(".search_by_company option:selected").val();
        let instance = $('.search_by_customer_type');
        let customer_type_id = instance.val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!customer_type_id) {
            customer_type_id = instance.attr("data-selectedCustomerTypeId");
        }

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}get-customer-type',
            'beforeSend': function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
            },
            success: function(response) {
                if (response.status) {
                    // console.log("getCountry 18",response.data, country_id);
                    let options = "<option value=''>Select Customer Type</option>";
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            if (customer_type_id == item.id) {
                                options += "<option value='" + item.id +
                                    "' selected >" + item.name + "</option>";
                            } else {
                                options += "<option value='" + item.id +
                                    "'>" +
                                    item.name + "</option>";
                            }
                        });
                    }
                    // console.log("getCountry 33",instance.parent());
                    if (instance.attr('data-append')) {
                        if ($(document).find("." + instance.attr('data-append')).length > 0) {
                            $("." + instance.attr('data-append')).empty();
                            $("." + instance.attr('data-append')).append(options);
                        } else {
                            $("." + instance.attr('data-append')).empty();
                            $("." + instance.attr('data-append')).append(options);
                        }
                    } else if (instance.parent().parent().hasClass("col-md-6") || instance
                        .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                        .hasClass("col-md-3")) {

                        let select_tag =
                            '<select name="customer_type_id" class="form-select search_by_customer_type';
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
                            html = '<div class="col-md-4 customer_type_div">';
                        } else if (instance.parent().parent().hasClass("col-md-3")) {
                            html = '<div class="col-md-3 customer_type_div">';
                        } else {
                            html = '<div class="col-md-6 customer_type_div">';
                        }
                        html +=
                            '<div class="form-group"> <label for="customer_type_id">Select Customer Type ';
                        if (is_required) {
                            html += '<span class="text-danger">*</span>';
                        }
                        html += '</label>';
                        html += select_tag;
                        html += '</div></div>';
                        $(".customer_type_div").remove();
                        instance.parent().parent().after(html);
                    } else if (instance.parent("tr")) {
                        // console.log("getcustomer_type 39", instance, instance.parent(), instance.next("th"));
                        $(".search_by_customer_type").removeClass('d-none').empty().append(options);
                        /*
                        // $(".search_by_customer_type").remove();
                        // instance.parent().next("th").remove();
                        // instance.parent().after('<th>'+options+'</th>');
                        */
                    } else {
                        console.log("getcustomer_type 82", instance.parent());
                        instance.parent().after(options);
                    }
                    $('.search_by_customer_type').trigger("change");
                    $('.select2').select2();
                }
            }
        });

    }
    $('.search_by_customer_type').trigger("change");
</script>
