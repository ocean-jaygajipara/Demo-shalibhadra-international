<script>
    $(document).ready(function () {
        if ($('meta[name="company_id"]').attr('value')) {
            console.log("L-4");
            fetch_companyArea();
        }

        $(document).on('change', '.search_by_company, .search_by_city', function () {
            console.log("L-10");
            fetch_companyArea();
        });

        function fetch_companyArea() {
            console.log("search_by_area L-14");
            let instance = $('.search_by_area');
            let company_id = $(".search_by_company option:selected").val();
            let country_id = $(".search_by_country option:selected").val();
            let state_id = $(".search_by_state option:selected").val();
            let city_id = $(".search_by_city option:selected").val();
            let area_id = instance.val();
            let is_required = instance.attr('required');
            let is_select2 = instance.hasClass('select2');

            if (!company_id && $('meta[name="company_id"]').attr('value')) {
                company_id = $('meta[name="company_id"]').attr('value');
            }

            if (!company_id) {
                company_id = $('.search_by_company').attr("data-selectedCompanyId");
            }
            if (!country_id) {
                country_id = $('.search_by_country').attr("data-selectedCountryId");
            }
            if (!state_id) {
                state_id = $('.search_by_state').attr("data-selectedStateId");
            }
            if (!city_id) {
                city_id = $('.search_by_city').attr("data-selectedCityId");
            }
            if (!area_id) {
                area_id = $('.search_by_area').attr("data-selectedAreaId");
            }

            // console.log(company_id, country_id, state_id, city_id);

            if (company_id && country_id && state_id && city_id) {
                console.log("search_by_area L-31");
                $.ajax({
                    type: 'POST',
                    url: '{{ env("API_URL") }}get-area',
                    beforeSend: function (request) {
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                    },
                    data: {
                        company_id: company_id,
                        country_id: country_id,
                        state_id: state_id,
                        city_id: city_id
                    },
                    success: function (response) {
                        if (response.status) {
                            let options = "<option value=''>Select Area</option>";
                            if (response.data && response.data.length > 0) {
                                $.each(response.data, function (i, item) {
                                    if (area_id == item.id) {
                                        options += "<option value='" + item.id + "' selected>" + item.area_name + "</option>";
                                    } else {
                                        options += "<option value='" + item.id + "'>" + item.area_name + "</option>";
                                    }
                                });
                            }

                            if ($(".search_by_area").attr('data-append')) {
                                let targetClass = "." + $(".search_by_area").attr('data-append');
                                if ($(document).find(targetClass).length > 0) {
                                    $(targetClass).empty().append(options);
                                } else {
                                    $(targetClass).empty().append(options);
                                }
                            } else if (
                                instance.parent().parent().hasClass("col-md-6") ||
                                instance.parent().parent().hasClass("col-md-4") ||
                                instance.parent().parent().hasClass("col-md-3")
                            ) {
                                let select_tag = '<select name="area_id" class="form-select search_by_area';
                                if (is_select2) {
                                    select_tag += ' select2';
                                }
                                select_tag += '"';
                                if (is_required) {
                                    select_tag += ' required';
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

                                html += '<div class="form-group">';
                                html += '<label for="state_id">Select City ';
                                if (is_required) {
                                    html += '<span class="text-danger">*</span>';
                                }
                                html += '</label>';
                                html += select_tag;
                                html += '</div></div>';

                                $(".country_div").remove();
                                instance.parent().parent().after(html);
                            } else if (instance.parent("tr")) {
                                $(".search_by_area").removeClass('d-none').empty().append(options);
                            } else {
                                console.log("getState 82", instance.parent());
                                instance.parent().after(options);
                            }

                            $('.select2').select2();
                        }
                    }
                });
            }
        }
        filter_state = $('select[name="state_id"] option:selected').val();
        if($.trim(filter_state || '') !== '') {
            $('.search_by_city').trigger("change");
        }
    });
</script>
