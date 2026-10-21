<script>
    let citySearchRequest = null; // Declare globally, outside the event

    $(document).on('change', '.search_by_state', function() {
        console.log("search_by_city L-3");

        let city_id = $(".search_by_city").attr("data-selectedCityId");
        let instance = $('.search_by_state');
        let country_id = $(".search_by_country option:selected").val();
        let state_id = instance.val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');

        if (!country_id) {

            country_id = $(".search_by_country").attr("data-selectedCountryId");
        }
        if (!state_id) {

            state_id = $(".search_by_state").attr("data-selectedStateId");
        }
        if (!city_id) {

            city_id = $(".search_by_city").attr("data-selectedCityId");
        }



        // Abort previous request if any
        if (citySearchRequest !== null) {
            console.log("citySearchRequest 27", citySearchRequest);

            citySearchRequest.abort();
        }

        citySearchRequest = $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}get-city',
            'beforeSend': function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
            },
            data: {
                country_id: country_id,
                state_id: state_id
            },
            success: function(response) {
                if (response.status) {
                    let options = "<option value=''>Select City</option>";
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(i, item) {

                            if (city_id == item.id) {
                                options += "<option value='" + item.id +
                                    "' selected >" + item.name + "</option>";
                            } else {
                                options += "<option value='" + item.id +
                                    "'>" +
                                    item.name + "</option>";
                            }
                        });

                        filter_city = $('select[name="city_id"] option:selected').val();
                        if ($.trim(filter_city || '') !== '') {
                            $('.search_by_city').trigger("change");
                        }
                    }
                    if ($(".search_by_city").attr('data-append')) {
                        if ($(document).find("." + $(".search_by_city").attr('data-append'))
                            .length > 0) {
                            $("." + $(".search_by_city").attr('data-append')).empty();
                            $("." + $(".search_by_city").attr('data-append')).append(options);
                        } else {
                            $("." + $(".search_by_city").attr('data-append')).empty();
                            $("." + $(".search_by_city").attr('data-append')).append(options);
                        }
                    } else if (instance.parent().parent().hasClass("col-md-6") || instance
                        .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                        .hasClass("col-md-3")) {

                        let select_tag =
                            '<select name="state_id" class="form-select search_by_state';
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
                        $(".search_by_state").removeClass('d-none').empty().append(options);
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

    });
    $('.search_by_state').trigger("change");
</script>
