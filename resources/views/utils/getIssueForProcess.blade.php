<script>
    let productIssueForProcessApiRequest = null; // Declare globally, outside the event

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_issueForProcess();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_issueForProcess();
    });

    function fetch_issueForProcess() {

        // Abort previous request if any
        if (productIssueForProcessApiRequest !== null) {
            productIssueForProcessApiRequest.abort();
        }

        let instance =  $('.search_by_issue_for_process_id');

        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let issue_for_process = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!issue_for_process) {
            issue_for_process = instance.attr("data-selectedIssueForProcessid");
        }

        if (company_id) {

            productIssueForProcessApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-issue-for-process') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Issue For Process</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (issue_for_process == item.id) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.issue_process + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.issue_process + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_issue_for_process_id").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_issue_for_process_id").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_issue_for_process_id").attr('data-append')).empty();
                                $("." + $(".search_by_issue_for_process_id").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_issue_for_process_id").attr('data-append')).empty();
                                $("." + $(".search_by_issue_for_process_id").attr('data-append')).append(options);
                            }
                        } else {
                            console.log("getState 82", instance.parent());
                            instance.parent().after(options);
                        }

                        $('select[name="issue_for_process[]"]').each(function () {
                            let selectedIssueForProcessid = $(this).data('selectedIssueForProcessid');
                            if (selectedIssueForProcessid) {
                                $(this).val(selectedIssueForProcessid).trigger('change');
                            }
                        });

                        $('.search_by_issue_for_process_id').trigger("change");
                        // $('.search_by_state').trigger("change");
                        //$('.select2').select2();
                    }
                }
            });
        }
    }
    $('.search_by_company').trigger("change");
</script>
