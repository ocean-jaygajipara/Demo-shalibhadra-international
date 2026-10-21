<script>
    let productGoDownApiRequest = null; // Declare globally, outside the event

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_productGoDown();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_productGoDown();
    });

    function fetch_productGoDown($row = null) {

        // Abort previous request if any
        if (productGoDownApiRequest !== null) {
            productGoDownApiRequest.abort();
        }

        let instance = $row ? $row.find('.search_by_product_godown') : $('.search_by_product_godown');

        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let godown_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!godown_id) {
            godown_id = instance.attr("data-selectedgodownid");
        }
        let is_edit = instance.attr("data-edit");

        if (company_id && is_edit) {

            productGoDownApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-product-godown') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select GoDown</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                options += "<option value='" + item.id +
                                        "'>" +
                                        item.product_godown + "</option>";
                            });
                        }

                        if ($row) {
                            let selectedGoDownId = instance.data("selectedgodownid");
                            instance.html(options); // set options
                            if (selectedGoDownId) {
                                instance.val(selectedGoDownId).trigger("change"); // only for this row
                            }
                            instance.select2(); // initialize select2
                        } else {
                            $('.search_by_product_godown').each(function() {
                                let $this = $(this);
                                let selectedGoDownId = $this.data("selectedgodownid");
                                $this.html(options);
                                if (selectedGoDownId) {
                                    $this.val(selectedGoDownId).trigger("change");
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
