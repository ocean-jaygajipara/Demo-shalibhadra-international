<script>
    fetch_company();
    function fetch_company() {
        console.log("search_by_country L-3");

        let instance = $('.search_by_country');
        let country_id = instance.val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let filterByStatus = instance.attr('data-filterByStatus');

        requestPayload = {};
        if (instance.attr('data-filterByStatus')) {
            requestPayload = {
                ...requestPayload,
                filter_by_status: instance.attr('data-filterByStatus')
            };
        }
        if (!country_id) {
            country_id = instance.attr("data-selectedCountryId");
        }
        let auto_selected = instance.attr("data-auto-selected");
        let customer_type_id = $("#customer_type_id").val();

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}get-country',
            'beforeSend': function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
            },
            data: requestPayload,
            success: function(response) {
                if (response.status) {
                    // console.log("getCountry 18",response.data, country_id);
                    let options = "<option value=''>Select Country</option>";
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            if (country_id == item.id && country_id) {
                                options += "<option value='" + item.id +
                                    "' selected >" + item.name + "</option>";
                            } else if(auto_selected == 'yes' && customer_type_id == 1){
                                let isSelected = ('IND' == item.short_name) ? 'selected' : '';
                                options += "<option value='" + item.id +
                                    "' "+isSelected+" >" + item.name + "</option>";
                            } else {
                                options += "<option value='" + item.id +
                                    "'>" +
                                    item.name + "</option>";
                            }
                        });
                    }
                     if(country_id != ''){
                        $('.search_by_country').trigger("change");
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
                            '<select name="country_id" class="form-select search_by_country';
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
                            html = '<div class="col-md-4 country_div">';
                        } else if (instance.parent().parent().hasClass("col-md-3")) {
                            html = '<div class="col-md-3 country_div">';
                        } else {
                            html = '<div class="col-md-6 country_div">';
                        }
                        html +=
                            '<div class="form-group"> <label for="country_id">Select Country ';
                        if (is_required) {
                            html += '<span class="text-danger">*</span>';
                        }
                        html += '</label>';
                        html += select_tag;
                        html += '</div></div>';
                        $(".country_div").remove();
                        instance.parent().parent().after(html);
                    } else if (instance.parent("tr")) {
                        // console.log("getCountry 39", instance, instance.parent(), instance.next("th"));
                        $(".search_by_country").removeClass('d-none').empty().append(options);
                        /*
                        // $(".search_by_country").remove();
                        // instance.parent().next("th").remove();
                        // instance.parent().after('<th>'+options+'</th>');
                        */
                    } else {
                        console.log("getCountry 82", instance.parent());
                        instance.parent().after(options);
                    }
                    if(country_id != ''){
                        $('.search_by_country').trigger("change");
                    }
                    $('.select2').select2();
                }
            }
        });
    }
    $('.search_by_country').trigger("change");
</script>
