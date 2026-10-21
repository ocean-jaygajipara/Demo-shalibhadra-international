<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companyComplainno();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_companyComplainno();
    });


    function fetch_companyComplainno() {
        // console.log("generate-complain-no L-3");
        let company_id = $(".search_by_company option:selected").val();

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            // console.log("generate-complain-no L-31");
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}generate-complain-no',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    // console.log("generate-complain-no L-31", response);
                    if (response.status) {
                        let code = '';
                        if (response.data) {
                            code = response.data;
                        }
                        $('#complain_no').val(code);
                    }
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
