<script>
    let productProcessReceivedApiRequest = null; // Declare globally, outside the event
    let productProcessReceivedApiLoading = false; // Flag to track if request is in progress

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_ProcessReceived();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_ProcessReceived();
    });

    function fetch_ProcessReceived() {
        // Prevent duplicate calls if request is already in progress
        if (productProcessReceivedApiLoading) {
            return;
        }

        let instance =  $('.search_by_process_receive_id');

        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let process_receive_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!process_receive_id) {
            process_receive_id = instance.attr("data-selectedprocessreceivedid");
        }

       let grid_process_receive = instance.attr("data-default-grid");
       let normalized_grid_process_receive = (grid_process_receive || '').trim().toLowerCase();
       let has_default_grid = ['yes', 'true', '1'].includes(normalized_grid_process_receive);

        if (company_id) {
            // Abort previous request if any
            if (productProcessReceivedApiRequest !== null) {
                productProcessReceivedApiRequest.abort();
            }

            // Show loading state
            productProcessReceivedApiLoading = true;
            if (window.ApiLoadingManager) {
                window.ApiLoadingManager.showLoading('.search_by_process_receive_id', {
                    showGlobalLoader: false, // Global loader handled by ajaxStart/ajaxStop
                    showElementLoader: true,
                    disableElement: true
                });
            }

            productProcessReceivedApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-process-received') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    productProcessReceivedApiLoading = false; // Reset loading flag on success

                    // Hide loading state
                    if (window.ApiLoadingManager) {
                        window.ApiLoadingManager.hideLoading('.search_by_process_receive_id');
                    }

                    if (response.status) {
                        let options = "<option value=''>Select Process Received</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                let isSelected = false;
                                if (process_receive_id == item.id) {
                                    isSelected = true;
                                } else if (!process_receive_id && i === 0 && has_default_grid) {
                                    // Auto-select first option when creating new record
                                    isSelected = true;
                                }

                                if (isSelected) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.process_received + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.process_received + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_process_receive_id").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_process_receive_id").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_process_receive_id").attr('data-append')).empty();
                                $("." + $(".search_by_process_receive_id").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_process_receive_id").attr('data-append')).empty();
                                $("." + $(".search_by_process_receive_id").attr('data-append')).append(options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="process_receive_id" class="form-select search_by_process_receive_id';
                            if (is_select2) {
                                select_tag += ' select2 ';
                            }
                            select_tag += '"';
                            if (is_required) {
                                select_tag += ' required ';
                            }
                            select_tag += '>' + options + '</select>';


                            let html = '';
                            if (instance.parent().parent().hasClass("col-md-4")) {
                                html = '<div class="col-md-4 state_div">';
                            } else if (instance.parent().parent().hasClass("col-md-3")) {
                                html = '<div class="col-md-3 state_div">';
                            } else {
                                html = '<div class="col-md-6 state_div">';
                            }
                            html +=
                                '<div class="form-group"> <label for="process_receive_id">Select Process Received ';
                            if (is_required) {
                                html += '<span class="text-danger">*</span>';
                            }
                            html += '</label>';
                            html += select_tag;
                            html += '</div></div>';
                            $(".country_div").remove();
                            instance.parent().parent().after(html);
                        } else if (instance.parent("tr")) {
                            $(".search_by_process_receive_id").removeClass('d-none').empty().append(options);
                        } else {
                            console.log("getState 82", instance.parent());
                            instance.parent().after(options);
                        }

                        // Reinitialize select2 if it was used
                        if (is_select2) {
                            $('.search_by_process_receive_id').select2();
                        }

                        $('.search_by_process_receive_id').trigger("change");
                    }
                },
                error: function(xhr, status, error) {
                    productProcessReceivedApiLoading = false; // Reset loading flag on error

                    // Hide loading state
                    if (window.ApiLoadingManager) {
                        window.ApiLoadingManager.hideLoading('.search_by_process_receive_id');
                    }

                    if (status !== 'abort') {
                        console.error("Process Received API error:", error);
                        // Optionally show error message to user
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            console.error("Error message:", xhr.responseJSON.message);
                        }
                    }
                },
                complete: function() {
                    productProcessReceivedApiRequest = null; // Reset request object
                    if (window.ApiLoadingManager) {
                        window.ApiLoadingManager.unregisterRequest('processReceived');
                    }
                }
            });

            // Register request for tracking
            if (window.ApiLoadingManager) {
                window.ApiLoadingManager.registerRequest('processReceived', productProcessReceivedApiRequest);
            }
        } else {
            // Hide loading if company_id is not available
            if (window.ApiLoadingManager) {
                window.ApiLoadingManager.hideLoading('.search_by_process_receive_id');
            }
        }
    }
    $('.search_by_company').trigger("change");
</script>
