<script>
    let productGradeApiRequest = null; // Declare globally, outside the event

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_productGrade();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_productGrade();
    });

    function fetch_productGrade() {

        // Abort previous request if any
        if (productGradeApiRequest !== null) {
            productGradeApiRequest.abort();
        }

        let instance = $('.search_by_product_grade');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let grade_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!grade_id) {
            grade_id = instance.attr("data-selectedGradeId");
        }

        if (company_id) {

            productGradeApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-product-grade') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Product Grade</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (grade_id == item.id) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.product_grade + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.product_grade + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_product_grade").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_product_grade").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_product_grade").attr('data-append')).empty();
                                $("." + $(".search_by_product_grade").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_product_grade").attr('data-append')).empty();
                                $("." + $(".search_by_product_grade").attr('data-append')).append(options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="product_grade_id" class="form-select search_by_product_grade';
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
                                '<div class="form-group"> <label for="product_grade_id">Select Product Grade ';
                            if (is_required) {
                                html += '<span class="text-danger">*</span>';
                            }
                            html += '</label>';
                            html += select_tag;
                            html += '</div></div>';
                            $(".country_div").remove();
                            instance.parent().parent().after(html);
                        } else if (instance.parent("tr")) {
                            $(".search_by_product_grade").removeClass('d-none').empty().append(options);
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
