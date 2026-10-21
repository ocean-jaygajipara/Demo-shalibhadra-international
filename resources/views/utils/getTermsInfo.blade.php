<script>
    $(document).on('change', '.search_by_terms', function() {
        console.log("search_by_terms_info L-3");
        let instance = $('.search_by_terms');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let terms_id = $(".search_by_terms option:selected").val();

        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id && terms_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-terms-info',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                    terms_id: terms_id,
                },
                success: function(response) {
                    if (response.status) {
                        ckeditorInstances['terms_and_condition_detail']?.setData(response?.data?.terms_and_condition_detail || '');
                    }
                }
            });
        }
    });
    $('.search_by_terms').trigger("change");
</script>
