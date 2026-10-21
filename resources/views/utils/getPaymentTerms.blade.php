<script>
    function getCompanyId() {
        let companyId = $('#company_id').val();
        if (!companyId && $('meta[name="company_id"]').attr('value')) {
            companyId = $('meta[name="company_id"]').attr('value');
        }
        return companyId;
    }
    $(document).on('change', '#payment_term_id', function() {
        updateCreditDays();
    });

    function updateCreditDays() {
    let selectedOption = $('#payment_term_id option:selected');
    let creditDays = selectedOption.data('credit-days') || '';

    if (!$('#credit_days').val()) {
        $('#credit_days').val(creditDays);
    }
}


    function fetchPaymentTerms() {
        const instance = $('#payment_term_id');
        let company_id = getCompanyId();
        let selectedTermId = instance.data('selectedpaymenttermid') || '';

        if (!company_id) return;

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}payment-terms/list',
            beforeSend: function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            data: {
                company_id: company_id
            },
            success: function(response) {
                if (response.status) {
                    let options = "<option value=''>Select Payment Term</option>";
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            options +=
                                `<option value="${item.id}" data-credit-days="${item.credit_days}" ${selectedTermId == item.id ? 'selected' : ''}>${item.payment_terms}</option>`;
                        });
                    }
                    instance.empty().append(options);

                    if (selectedTermId) {
                        instance.val(selectedTermId).trigger('change');
                        updateCreditDays();
                    }
                }
            }
        });
    }


    $(document).on('change', '#company_id', function() {
        fetchPaymentTerms();
    });

    $(document).ready(function() {
        if (getCompanyId()) {
            fetchPaymentTerms();
        }
    });
</script>
