<script>
    let productAlloyApiRequest = null; // Declare globally, outside the event

    // if ($('meta[name="company_id"]').attr('value')) {
    //     fetch_productAlloy();
    // }

    // $(document).on('change', '.search_by_company', function() {
    //     fetch_productAlloy();
    // });

    function fetch_productAlloy() {

        // Abort previous request if any
        if (productAlloyApiRequest !== null) {
            productAlloyApiRequest.abort();
        }

        let instance = $('.search_by_product_alloy');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let alloy_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!alloy_id) {
            alloy_id = instance.attr("data-selectedAlloyId");
        }

        if (company_id) {

            productAlloyApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-product-alloy') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Product Alloy</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (alloy_id == item.id) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.product_alloy + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.product_alloy + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_product_alloy").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_product_alloy").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_product_alloy").attr('data-append')).empty();
                                $("." + $(".search_by_product_alloy").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_product_alloy").attr('data-append')).empty();
                                $("." + $(".search_by_product_alloy").attr('data-append')).append(options);
                            }
                        }
                        // $('.search_by_state').trigger("change");
                         $('#alloy').select2({
                            dropdownParent: $('#product_rm_detail_modal')
                        });
                    }
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
