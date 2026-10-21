<script>

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_vendorInfo();
    } else {
        $(document).on('change', '.search_by_vendor', function() {
            if ($(".search_by_vendor option:selected").val() != null && $(".search_by_vendor option:selected")
                .val() !== "") {
                fetch_vendorInfo();
            }
        });
    }
    let isInitialVendorLoad = !!$("#vendor_id").data('selectedvendorid');
    function fetch_vendorInfo() {
        let instance = $('.search_by_vendor');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let vendor_id = $(".search_by_vendor option:selected").val();

        if (!company_id) {
            company_id = $("#company_id").attr("data-selectedCompanyId");
        }

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!vendor_id) {
            vendor_id = instance.attr("data-selectedVendorId");
        }

        if (company_id && vendor_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-vendor-info',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                    vendor_id: vendor_id,
                },
                success: function(response) {
                    if (response.status) {
                        let vendorInfo = response.data;
                        $("#gst_no").val(vendorInfo.gst_number);

                        if (!isInitialVendorLoad) {
                            $("#vendor_name").val(vendorInfo.vendor_name);
                            $("#vendor_name_display").text(vendorInfo.vendor_name);

                            $("#address").val(vendorInfo.address);
                            $("#address_display").text(vendorInfo.address);

                            $("#phone").val(vendorInfo.mobile_number);
                            $("#phone_display").text(vendorInfo.mobile_number);

                            $("#state").val(vendorInfo.state.name);
                            $("#state_display").text(vendorInfo.state.name);

                            $("#state_id").attr("data-selectedStateId", vendorInfo?.state?.id ?? '');
                            $("#state_id").attr("data-selectedCountryId", vendorInfo?.country?.id ?? '');

                            $("#country_id").val(vendorInfo?.country?.id ?? '').trigger("change");

                            $("#shipping_address").val(vendorInfo.shipping_address);
                            $("#billing_address").val(vendorInfo.billing_address);
                        }
                        isInitialVendorLoad = false;
                    }
                }
            });
        }
    }
    $('.search_by_vendor').trigger("change");
</script>
