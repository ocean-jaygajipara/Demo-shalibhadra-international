<script>
    let productLocationApiRequest = null; // Declare globally, outside the event

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_productLocation();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_productLocation();
    });

    function fetch_productLocation($row = null) {

        // Abort previous request if any
        if (productLocationApiRequest !== null) {
            productLocationApiRequest.abort();
        }


        let instance = $row ? $row.find('.search_by_product_location') : $('.search_by_product_location');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let location_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!location_id) {
            location_id = instance.attr("data-selectedlocationid");
        }
        let is_edit = instance.attr("data-edit");

        if (company_id && is_edit) {

            productLocationApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-product-location') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Location</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                options += "<option value='" + item.id +
                                    "'>" +
                                    item.product_location + "</option>";
                            });
                        }



                        if ($row) {
                            let selectedlocationid = instance.data("selectedlocationid");
                            instance.html(options); // set options
                            if (selectedlocationid) {
                                instance.val(selectedlocationid).trigger("change"); // only for this row
                            }
                            instance.select2(); // initialize select2
                        } else {
                            $('.search_by_product_location').each(function() {
                                let $this = $(this);
                                let selectedlocationid = $this.data("selectedlocationid");
                                $this.html(options);
                                if (selectedlocationid) {
                                    $this.val(selectedlocationid).trigger("change");
                                }
                                $this.select2();
                            });
                        }

                    }
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
