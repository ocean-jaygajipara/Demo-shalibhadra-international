<script>
    $(document).ready(function() {
        // Trigger once on page load
        fetch_generateClientCode();

        // Trigger on company change
        $(document).on('change', '.search_by_company', function() {
            fetch_generateClientCode();
        });
    });

    function fetch_generateClientCode() {
        let company_id = $(".search_by_company option:selected").val();

        // Also check meta if not selected from dropdown (for hidden input case)
        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            // Enable and show client code input
            $('#client_code').prop('disabled', false);

            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}generate-client-code',
                beforeSend: function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status && response.data) {
                        $('#client_code').val(response.data);
                    }
                }
            });
        } else {
            // Disable and clear client code if company is not selected
            $('#client_code').val('');
            $('#client_code').prop('disabled', true);
        }
    }
</script>
