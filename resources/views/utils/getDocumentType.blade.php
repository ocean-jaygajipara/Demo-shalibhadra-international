<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_documentType();
    }


    $(document).on('change', '.search_by_company', function() {
        fetch_documentType();
    });

    function fetch_documentType() {

       let instance = $('.search_by_company');
        let company_id = instance.val();
        let document_type_option = $('#document_type_id');
        let selectedDocumentTypeId = document_type_option.attr("data-selectedDocumentTypeId");

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }


        if (!company_id) {
            document_type_option.html("<option value=''>Select Document Type</option>");
            return;
        }

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}get-document-type',
            beforeSend: function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            data: {
                company_id: company_id
            },
            success: function(response) {
                if (response.status) {
                    let options = "<option value=''>Select Document Type</option>";
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            if (selectedDocumentTypeId == item.id) {
                                options += "<option value='" + item.id + "' selected>" + item
                                    .document_type_name + "</option>";
                            } else {
                                options += "<option value='" + item.id + "'>" + item
                                    .document_type_name + "</option>";
                            }
                        });
                    }

                    document_type_option.html(options);

                    if (document_type_option.hasClass('select2')) {
                        document_type_option.select2();
                    }
                }
            }
        });
    }


    $('.search_by_company').trigger("change");
</script>
