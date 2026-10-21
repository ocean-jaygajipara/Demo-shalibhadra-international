<script>

    fetch_plans();

    function fetch_plans() {
        let instance = $('.search_by_plan');
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}get-plans',
            'beforeSend': function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                    'content'));
            },
            success: function(response) {
                if (response.status) {
                    let options = "<option value=''>Select Plans</option>";
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            options += "<option value='" + item.id +
                                "'>" +
                                item.name + "</option>";
                        });
                    }
                    if ($(".search_by_plan").attr('data-append')) {
                        if ($(document).find("." + $(".search_by_plan").attr('data-append'))
                            .length > 0) {
                            $("." + $(".search_by_plan").attr('data-append')).empty();
                            $("." + $(".search_by_plan").attr('data-append')).append(options);
                        } else {
                            $("." + $(".search_by_plan").attr('data-append')).empty();
                            $("." + $(".search_by_plan").attr('data-append')).append(options);
                        }
                    } else if (instance.parent().parent().hasClass("col-md-6") || instance
                        .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                        .hasClass("col-md-3")) {

                        let select_tag =
                            '<select name="plan_id" class="form-select search_by_plan';
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
                            '<div class="form-group"> <label for="plan_id">Select Plans ';
                        if (is_required) {
                            html += '<span class="text-danger">*</span>';
                        }
                        html += '</label>';
                        html += select_tag;
                        html += '</div></div>';
                        instance.parent().parent().after(html);
                    } else if (instance.parent("tr")) {
                        $(".search_by_plan").removeClass('d-none').empty().append(options);
                    } else {
                        instance.parent().after(options);
                    }
                    $('.select2').select2();
                }
            }
        });
    }
</script>
