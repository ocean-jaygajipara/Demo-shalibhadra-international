<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companyTerm();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            fetch_companyTerm();
        }
    });


    function fetch_companyTerm() {
        // console.log("search_by_terms L-3");
        let instance = $('.search_by_terms');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let terms_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }

        if (!terms_id) {
            terms_id = instance.attr("data-selectedTermsId");
        }

        if (company_id) {
            // console.log("search_by_terms L-31");
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-terms',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Terms</option>";
                        let firstTermId = null;
                        let autoSelectedTermId = null; // Track if first term was auto-selected
                        if (response.data && response.data.length > 0) {
                            // Store first term ID for auto-selection
                            firstTermId = response.data[0].id;
                            
                            $.each(response.data, function(i, item) {
                                let isSelected = false;
                                if (terms_id == item.id) {
                                    isSelected = true;
                                } else if (!terms_id && i === 0) {
                                    // Auto-select first option when creating new record
                                    isSelected = true;
                                    autoSelectedTermId = item.id; // Store the auto-selected term ID
                                }
                                
                                if (isSelected) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.terms_name + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.terms_name + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_terms").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_terms").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_terms").attr('data-append')).empty();
                                $("." + $(".search_by_terms").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_terms").attr('data-append')).empty();
                                $("." + $(".search_by_terms").attr('data-append')).append(options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="terms_id" class="form-select search_by_terms';
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
                                '<div class="form-group"> <label for="state_id">Select Terms ';
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
                        
                        // If first term was auto-selected or a term was already selected, trigger change event to load terms info
                        let selectedTermId = autoSelectedTermId || terms_id;
                        if (selectedTermId) {
                            // Use setTimeout to ensure Select2 is fully initialized and DOM is updated
                            setTimeout(function() {
                                $('.search_by_terms').val(selectedTermId).trigger('change');
                            }, 100);
                        }
                    }
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
