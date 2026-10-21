<script>

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companyQuotationsno();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            fetch_companyQuotationsno();
        }
    });


    function fetch_companyQuotationsno() {
        console.log("generate-quotation-no L-3");
        let company_id = $(".search_by_company option:selected").val();

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }


        if (company_id) {
            // console.log("generate-quotation-no L-31");
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}generate-quotation-no',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    // console.log("generate-quotation-no L-31", response);
                    if (response.status) {
                        let code = '';
                        if (response.data) {
                            code = response.data;
                        }
                        $('#quotation_no').val(code);
                    }
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
