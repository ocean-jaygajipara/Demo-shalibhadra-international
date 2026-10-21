<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_customerInfo();
    } else {
        $(document).on('change', '.search_by_company', function () {
            if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected")
                .val() !== "") {
                fetch_customerInfo();
            }
        });
    }
    // 🔄 Listen for customer change
    $(document).on('change', '.search_by_customer', function () {
        fetch_customerInfo();
    });
    let isInitialCustomerLoad = !!$('.search_by_customer').data('selectedcustomerid');
    function fetch_customerInfo() {
        // console.log("search_by_customer_info L-3");
        let instance = $('.search_by_customer');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let customer_id = $(".search_by_customer option:selected").val();

        let selectedCustomerId = $('#selected_customer_id').val();
        // console.log(selectedCustomerId);

        if (selectedCustomerId) {
            customer_id = selectedCustomerId;
            $(".search_by_customer").val(selectedCustomerId).trigger('change.select2');
        }

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }

        if (!customer_id) {
            customer_id = $("#customer_id").val();
        }

        if (!customer_id) {
            customer_id = instance.attr("data-selectedCustomerId");
        }

        if (company_id && customer_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-customer-info',
                'beforeSend': function (request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                    customer_id: customer_id,
                },
                success: function (response) {
                    if (response.status) {
                        let customerInfo = response.data;
                        $("#is_igst").val(customerInfo.is_igst);

                        if (!isInitialCustomerLoad) {
                            $("#customer_name").val(customerInfo.customer_name);
                            $("#customer_name_display").text(customerInfo.customer_name);

                            $("#contact_person_name").val(customerInfo.contact_person_name);
                            $("#contact_person_name_display").text(customerInfo.contact_person_name);

                            $("#address").val(customerInfo.address);
                            $("#address_display").text(customerInfo.address);

                            $("#city").val(customerInfo?.city?.name);
                            $("#city_display").text(customerInfo?.city?.name);

                            $("#pincode").val(customerInfo.pincode);
                            $("#pincode_display").text(customerInfo.pincode);

                            $("#phone").val(customerInfo.mobile_number);
                            $("#phone_display").text(customerInfo.mobile_number);

                            $("#gst_no").val(customerInfo.gst_number);

                            $("#state").val(customerInfo?.state?.name);
                            $("#state_display").text(customerInfo?.state?.name);
                            $("#state_id").attr("data-selectedStateId", customerInfo?.state?.id ?? '');
                            $("#state_id").attr("data-selectedCountryId", customerInfo?.country?.id ?? '');

                            $("#country_id").val(customerInfo?.country?.id ?? '').trigger("change");

                            $("#price_list_id").val(customerInfo?.price_list?.id ?? '');
                            $("#price_list_display").text(customerInfo?.price_list?.name ?? '');

                            $("#shipping_address").val(customerInfo.shipping_address);
                            $("#billing_address").val(customerInfo.billing_address);
                        }

                        let is_igst = parseInt($("#is_igst").val()) || 0;
                        if (is_igst == 1) {
                            $(".cgst_div").hide();
                            $(".sgst_div").hide();
                            $(".igst_div").show();
                        } else {
                            $(".cgst_div").show();
                            $(".sgst_div").show();
                            $(".igst_div").hide();
                        }
                        isInitialCustomerLoad = false;
                    }
                }
            });
        }
    }

    let selectedCustomer = $("#customer_id").data("selectedcustomerid");

    if (selectedCustomer) {
        $("#customer_id").val(selectedCustomer).trigger("change");
    }
</script>