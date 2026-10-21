<script>

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companySalesOrderno();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            fetch_companySalesOrderno();
        }
    });

    function fetch_companySalesOrderno() {
        console.log("generate-sales-order-no L-3");
        let company_id = $(".search_by_company option:selected").val();

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            console.log("generate-sales-order-no L-31");
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}generate-sales-order-no',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    console.log("generate-sales-order-no L-31", response);
                    if (response.status) {
                        let code = '';
                        if (response.data) {
                            code = response.data;
                        }
                        $('#sales_order_no').val(code);
                    }
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
