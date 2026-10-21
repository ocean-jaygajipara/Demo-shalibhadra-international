<script>
    $(document).ready(function () {
        if ($('meta[name="company_id"]').attr('value')) {
            fetch_paymentReceiptType();
        }

        $(document).on('change', '.search_by_company', function () {
            fetch_paymentReceiptType();
        });

        function fetch_paymentReceiptType() {
            let companyId = $(".search_by_company").val();
            let metaCompanyId = $('meta[name="company_id"]').attr('value');
            let $select = $('#receipt_type');
            let receipt_type_id = '';

            if (!companyId && metaCompanyId) {
                companyId = metaCompanyId;
            }

            if (companyId) {
                receipt_type_id = $select.attr("data-selectedReceiptTypeId");
            }

            if (companyId) {
                $.ajax({
                    type: "POST",
                    url: '{{ env('API_URL') }}get-payment-receipt-type',
                    'beforeSend': function(request) {
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                            'content'));
                    },
                    data: {
                        company_id: companyId,
                    },
                    success: function (response) {
                        if(response.status == true) {
                            var receiptTypes = response.data;

                            $select.empty();
                            $select.append('<option value="">Select Receipt Type</option>'); // default
                            $.each(receiptTypes, function(key, value) {
                                if (value !== '') {
                                    if (receipt_type_id == key) {
                                        $select.append('<option value="' + key + '" selected>' + value + '</option>');
                                    } else {
                                        $select.append('<option value="' + key + '">' + value + '</option>');
                                    }

                                }
                            });
                            $select.trigger('change');
                        }
                    }
                });
            }
        }

        // Preload if edit
        const selectedCompanyId = $('#company_id').data('selectedcompanyid');
        if (selectedCompanyId) {
            $('#company_id').val(selectedCompanyId).trigger('change');
        }
    });
</script>
