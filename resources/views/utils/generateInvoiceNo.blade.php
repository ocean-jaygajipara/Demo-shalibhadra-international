<script>

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companyInvoiceno();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            fetch_companyInvoiceno();
        }
    });

    function fetch_companyInvoiceno() {
        let company_id = $(".search_by_company option:selected").val();

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}generate-invoice-no',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let code = '';
                        if (response.data) {
                            code = response.data;
                        }
                        $('#invoice_order_no').val(code);
                    }
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
