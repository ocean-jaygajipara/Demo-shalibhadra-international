<script>
    let productRackApiRequest = null; // Declare globally, outside the event

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_productRack();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_productRack();
    });

    function fetch_productRack($row = null) {

        // Abort previous request if any
        if (productRackApiRequest !== null) {
            productRackApiRequest.abort();
        }

        let instance = $row ? $row.find('.search_by_product_rack') : $('.search_by_product_rack');

        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let rack_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!rack_id) {
            rack_id = instance.attr("data-selectedRackId");
        }

        if (company_id) {

            productRackApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-product-rack') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Product Rack</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                options += "<option value='" + item.id +"'>" +item.product_rack + "</option>";
                            });
                        }

                        if ($row) {
                            let selectedRackId = instance.data("selectedRackId");
                            instance.html(options); // set options
                            if (selectedRackId) {
                                instance.val(selectedRackId).trigger("change"); // only for this row
                            }
                            instance.select2(); // initialize select2
                        } else {
                            $('.search_by_product_rack').each(function() {
                                let $this = $(this);
                                let selectedRackId = $this.data("selectedRackId");
                                $this.html(options);
                                if (selectedRackId) {
                                    $this.val(selectedRackId).trigger("change");
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
