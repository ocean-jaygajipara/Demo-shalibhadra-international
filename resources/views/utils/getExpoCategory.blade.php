<script>
    let ExpoCategoryApiRequest = null; // Declare globally, outside the event

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_expocategory();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_expocategory();
    });

    function fetch_expocategory() {

        // Abort previous request if any
        if (ExpoCategoryApiRequest !== null) {
            ExpoCategoryApiRequest.abort();
        }

        // console.log("product variant L-3");
        let instance = $('.search_by_expo_category');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }


        let selectedcategoryid = instance.attr("data-selectedcategoryid");

        if (company_id) {
            // console.log("search_by_product_variant L-31");
            ExpoCategoryApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('api.get-expo-category') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    // company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Expo Category</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (selectedcategoryid == item.id) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.name + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.name + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_expo_category").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_expo_category").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_expo_category").attr('data-append')).empty();
                                $("." + $(".search_by_expo_category").attr('data-append')).append(
                                    options);
                            } else {
                                $("." + $(".search_by_expo_category").attr('data-append')).empty();
                                $("." + $(".search_by_expo_category").attr('data-append')).append(
                                    options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="expo_category_id" class="form-select search_by_expo_category';
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
                                '<div class="form-group"> <label for="expo_category_id">Select Expo Category';
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
                            $(".search_by_expo_category").removeClass('d-none').empty().append(options);
                            /*
                            // $(".search_by_country").remove();
                            // instance.parent().next("th").remove();
                            // instance.parent().after('<th>'+options+'</th>');
                            */
                        } else {
                            // console.log("getState 82", instance.parent());
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
