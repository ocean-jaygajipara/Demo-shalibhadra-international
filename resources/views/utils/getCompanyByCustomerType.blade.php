<script>
    $(document).ready(function () {
        if ($('meta[name="company_id"]').attr('value')) {
            fetch_companyCustomerType();
        }

        $(document).on('change', '.search_by_company', function () {
            fetch_companyCustomerType();
        });

        function fetch_companyCustomerType() {
            let companyId = $(".search_by_company").val();
            const metaCompanyId = $('meta[name="company_id"]').attr('value');
            const selectedCustomerTypeId = $('#customer_type_id').data('selectedcustomertypeid');

            if (!companyId && metaCompanyId) {
                companyId = metaCompanyId;
            }

            if (!companyId) {
                $('#customer_type_id').html('<option value="">Select Customer Type</option>');
                $('#parent_id').html('<option value="">Select Parent Type</option>');
                $('#parent_type_div').hide();
                return;
            }

            $.ajax({
                url: '{{ env('API_URL') }}customer/type/list',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    company_id: companyId
                },
                success: function (response) {
                    if (response.status === true) {
                        let customerOptions = '<option value="">Select Customer Type</option>';

                        response.data.forEach(item => {
                            const isSelected = selectedCustomerTypeId == item.id ? 'selected' : '';
                            customerOptions += `<option value="${item.id}" data-parent_id="${item.parent_id ?? ''}" ${isSelected}>${item.name}</option>`;
                        });

                        $('#customer_type_id').html(customerOptions).trigger('change');
                    } else {
                        alert('Failed to load customer types.');
                    }
                },
                error: function () {
                    alert('Server error while fetching customer types.');
                }
            });
        }

        // Preload if edit
        const selectedCompanyId = $('#company_id').data('selectedcompanyid');
        if (selectedCompanyId) {
            $('#company_id').val(selectedCompanyId).trigger('change');
        }
    });
</script>
