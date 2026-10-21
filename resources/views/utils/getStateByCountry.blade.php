<script>
    let stateSearchRequest = null; // Declare globally, outside the event

    $(document).on('change', '.search_by_country', function () {
        console.log("search_by_state L-3");

        let instance = $('.search_by_country');
        let country_id = instance.val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let state_id = instance.attr("data-selectedStateId");
        if (!country_id) {
            country_id = instance.attr("data-selectedCountryId");
        }

        if (!state_id) {
            state_id = $(".search_by_state").attr("data-selectedStateId");
        }

        // Abort previous request if any
        if (stateSearchRequest !== null) {
            stateSearchRequest.abort();
        }

        stateSearchRequest = $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}get-state',
            'beforeSend': function (request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
            },
            data: {
                country_id: country_id
            },
            success: function (response) {
                if (response.status) {
                    // console.log("getState 30",response.data, country_id, state_id);
                    let options = "<option value=''>Select State</option>";
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function (i, item) {
                            if (parseInt(state_id) === parseInt(item.id)) {
                                options += "<option value='" + item.id +
                                    "' selected >" + item.name + "</option>";
                            } else {
                                options += "<option value='" + item.id +
                                    "'>" +
                                    item.name + "</option>";
                            }
                        });
                    }
                    // console.log("getState 33",instance.parent(),);
                    if ($(".search_by_state").attr('data-append')) {
                        if ($(document).find("." + $(".search_by_state").attr('data-append'))
                            .length > 0) {
                            $("." + $(".search_by_state").attr('data-append')).empty();
                            $("." + $(".search_by_state").attr('data-append')).append(options);
                        } else {
                            $("." + $(".search_by_state").attr('data-append')).empty();
                            $("." + $(".search_by_state").attr('data-append')).append(options);
                        }
                    }
                    $('.select2').select2();
                }
            }

        });
    });
    $('.search_by_state').trigger("change");
</script>