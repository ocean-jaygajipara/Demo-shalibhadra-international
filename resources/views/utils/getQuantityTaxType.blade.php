<script>

    let quantityTaxTypeApiRequest = null; // Declare globally, outside the event
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companyQuantityTaxType();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            fetch_companyQuantityTaxType();
        }
    });


    function fetch_companyQuantityTaxType() {

        // Abort previous request if any
        if (quantityTaxTypeApiRequest !== null) {
            quantityTaxTypeApiRequest.abort();
        }

        let company_id = $(".search_by_company option:selected").val();

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }


        if (company_id) {
            quantityTaxTypeApiRequest = $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-quantity-tax-type',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let companyQuantity = '';
                        let companyTaxType = '';
                        if (response.data) {
                            companyQuantity = response.data[0].quantity;
                            $("#qty_is_value").val(response.data[0].quantity);
                            companyTaxType = response.data[0].tax_type;
                        }

                        if(companyQuantity?.trim()) {
                            const companyQuantityArr = companyQuantity.split(',').map(item => item.trim());

                            const toggleVisibility = (type, selector) => {
                                $(selector).toggle(companyQuantityArr.includes(type));
                            };

                            toggleVisibility('bag', '.inner_main_dv');
                            toggleVisibility('box', '.outer_main_dv');
                            toggleVisibility('kg', '.kg_main_dv');

                            // Hide .loose_main_dv if quantity is provided
                            if (companyQuantityArr.includes('pcs') || companyQuantityArr.includes('kg')) {
                                $('.loose_main_dv').hide();
                            } else {
                                $('.loose_main_dv').show();
                            }
                            if (companyQuantityArr.includes('kg')) {
                                $('.quantity').attr('readonly', true);
                                $('.quantity').addClass("bg-light");
                            } else {
                                $('.quantity').removeAttr('readonly');
                                $('.quantity').removeClass("bg-light");
                            }
                        } else {
                            $('.loose_main_dv').show();

                            $('.inner_main_dv, .outer_main_dv, .kg_main_dv').hide();
                            $('.quantity').removeAttr('readonly');
                            $('.quantity').removeClass("bg-light");
                        }

                        if(companyTaxType?.trim()) {
                            $(".tax_type_dv").toggle(companyTaxType.trim() === 'product_wise');
                        }
                    }
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
