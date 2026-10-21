<script>
    let productLastProcessApiRequest = null; // Declare globally, outside the event

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_lastProcess();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_lastProcess();
    });

    function fetch_lastProcess($row = null) {

        // Abort previous request if any
        if (productLastProcessApiRequest !== null) {
            productLastProcessApiRequest.abort();
        }

        let instance = $row ? $row.find('.search_by_last_process') : $('.search_by_last_process');

        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let last_process_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!last_process_id) {
            last_process_id = instance.attr("data-selectedlastprocessid");
        }
        let is_edit = instance.attr("data-edit");

        if (company_id && is_edit) {

            productLastProcessApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-last-process') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Last Process</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                options += "<option value='" + item.id +"'>" +item.last_process + "</option>";
                            });
                        }
                        if ($row) {
                            let selectedlastprocessid = instance.data("selectedlastprocessid");
                            instance.html(options); // set options
                            if (selectedlastprocessid) {
                                instance.val(selectedlastprocessid).trigger("change"); // only for this row
                            }
                            instance.select2(); // initialize select2
                        } else {
                            $('.search_by_last_process').each(function() {
                                let $this = $(this);
                                let selectedlastprocessid = $this.data("selectedlastprocessid");
                                $this.html(options);
                                if (selectedlastprocessid) {
                                    $this.val(selectedlastprocessid).trigger("change");
                                }
                                $this.select2();
                            });
                        }
                    }
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
