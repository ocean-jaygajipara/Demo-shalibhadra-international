<script>
    $(document).ready(function () {
        fetch_companyQuotationsno(); // Run on page load
    });

    $(document).on('change', '.search_by_company', function() {
        fetch_companyQuotationsno(); // Run again if company is manually changed
    });

    function fetch_companyQuotationsno() {
        // 🚫 Do NOT regenerate if receipt_no already exists (edit mode)
        if ($('#receipt_no').val()) {
            return; // Exit early, no need to regenerate
        }

        let company_id = $(".search_by_company").val();

        if (!company_id) {
            company_id = $('meta[name="company_id"]').attr('content');
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}generate-receipt-no',
                beforeSend: function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status && response.data) {
                        $('#receipt_no').val(response.data);
                    }
                }
            });
        }
    }
</script>
