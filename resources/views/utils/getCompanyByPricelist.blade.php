<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companyPricelist();
    }

    $(document).on('change', '.search_by_company', function () {
        fetch_companyPricelist();
    });

    function fetch_companyPricelist() {
        let company_id = $(".search_by_company option:selected").val();

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-price-lists',
                beforeSend: function (request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function (response) {
                    if (response.status) {
                        const priceListSelect = $('#price_list_id');
                        const price_list_id = priceListSelect.data('selectedpricelistid');

                        let options = "<option value=''>Select Price List</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function (i, item) {
                                if (price_list_id == item.id) {
                                    options += "<option value='" + item.id + "' selected>" + item.name + "</option>";
                                } else {
                                    options += "<option value='" + item.id + "'>" + item.name + "</option>";
                                }
                            });
                        }
                        priceListSelect.html(options);
                        priceListSelect.trigger('change.select2');
                    }
                },
            });
        }
    }

    $('.search_by_company').trigger("change");
</script>
