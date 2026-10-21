<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companyCustomer();
    }

    $(document).on('change', '.search_by_company, .search_by_customer_type', function() {
        fetch_companyCustomer();
    });

    function fetch_companyCustomer() {
        let customer_id = $(".search_by_customer").attr("data-selectedCustomerId");
        let instance = $('.search_by_customer_type');
        let company_id = $(".search_by_company option:selected").val();
        let customer_type_id = instance.val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let customer_person_name = instance.attr('data-personName');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }
        if (!customer_type_id) {
            customer_type_id = instance.attr("data-selectedCustomerTypeId");
        }
        if (!customer_id) {
            customer_id = $(".search_by_customer").attr("data-selectedCustomerId");
        }

        if (company_id && customer_type_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-customer',
                beforeSend: function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: {
                    company_id: company_id,
                    customer_type_id: customer_type_id
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Customer</option>";
                        let customerSelect = $(".search_by_customer");
                        let module = customerSelect.data("module");

                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                let label = '';


                                if (customer_person_name && customer_person_name.trim() !==
                                    '' && customer_person_name == 'yes') {

                                    label = (item.customer_name || '-') + ' - ' + item
                                        .contact_person_name;

                                } else {

                                    label = item.customer_name || '-';

                                }


                                // if (item.contact_person_name && item.contact_person_name.trim() !== '') {
                                //     label = (item.customer_name || '-') + ' - ' + item.contact_person_name;
                                // } else {
                                //     label = item.customer_name || '-';
                                // }

                                let selected = (customer_id == item.id) ? 'selected' : '';
                                options +=
                                    `<option value="${item.id}" ${selected}>${label}</option>`;
                            });

                            if (customer_id != '') {
                                customerSelect.trigger("change");
                            }
                        }

                        // Append or update dropdown
                        if ($(".search_by_customer").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_customer").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_customer").attr('data-append')).empty().append(
                                    options);
                            } else {
                                $("." + $(".search_by_customer").attr('data-append')).empty().append(
                                    options);
                            }
                        } else if (
                            instance.parent().parent().hasClass("col-md-6") ||
                            instance.parent().parent().hasClass("col-md-4") ||
                            instance.parent().parent().hasClass("col-md-3")
                        ) {
                            let select_tag =
                                '<select name="customer_id" class="form-select search_by_customer';
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
                                html = '<div class="col-md-4 customer_div">';
                            } else if (instance.parent().parent().hasClass("col-md-3")) {
                                html = '<div class="col-md-3 customer_div">';
                            } else {
                                html = '<div class="col-md-6 customer_div">';
                            }
                            html += '<div class="form-group"> <label for="customer_id">Select Customer';
                            if (is_required) {
                                html += '<span class="text-danger">*</span>';
                            }
                            html += '</label>' + select_tag + '</div></div>';
                            $(".customer_div").remove();
                            instance.parent().parent().after(html);
                        } else if (instance.parent("tr")) {
                            $(".search_by_customer").removeClass('d-none').empty().append(options);
                        } else {
                            console.log("getCustomer 82", instance.parent());
                            instance.parent().after(options);
                        }

                        // Reinitialize Select2
                        $('.select2').select2();
                    }
                }
            });
        }
    }

    $('.search_by_company').trigger("change");
</script>
