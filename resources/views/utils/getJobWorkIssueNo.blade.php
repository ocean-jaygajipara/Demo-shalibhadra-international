<script>
    let jobWorkIssueNoApiRequest = null; // Declare globally, outside the event
    let jobWorkIssueNoApiLoading = false; // Flag to track if request is in progress

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_JobWorkIssueNo();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_JobWorkIssueNo();
    });

    function fetch_JobWorkIssueNo() {
        // Prevent duplicate calls if request is already in progress
        if (jobWorkIssueNoApiLoading) {
            return;
        }

        let instance =  $('.search_by_job_work_issue_no');

        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let job_work_issue_no_id = '';
        let defaultFirstWarehouse = instance.attr('data-defaultFirst'); // Warehouse name like "Finish Good" or "Bhathi"
        let selectValue = instance.attr('value');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if (!job_work_issue_no_id) {
            job_work_issue_no_id = instance.attr("data-selectedJobWorkIssueNo");
        }
        // If value attribute is provided, use it
        if (!job_work_issue_no_id && selectValue) {
            job_work_issue_no_id = selectValue;
        }

        if (company_id) {
            // Abort previous request if any
            if (jobWorkIssueNoApiRequest !== null) {
                jobWorkIssueNoApiRequest.abort();
            }

            // Show loading state
            jobWorkIssueNoApiLoading = true;
            if (window.ApiLoadingManager) {
                window.ApiLoadingManager.showLoading('.search_by_job_work_issue_no', {
                    showGlobalLoader: false, // Global loader handled by ajaxStart/ajaxStop
                    showElementLoader: true,
                    disableElement: true
                });
            }

            jobWorkIssueNoApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-job-work-issue-no') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    jobWorkIssueNoApiLoading = false; // Reset loading flag on success
                    
                    // Hide loading state
                    if (window.ApiLoadingManager) {
                        window.ApiLoadingManager.hideLoading('.search_by_job_work_issue_no');
                    }
                    if (response.status) {
                        let options = "<option value=''>Select Job Work Issue No</option>";
                        let firstItemId = null;
                        if (response.data && response.data.length > 0) {
                            // Store first item ID for auto-selection
                            firstItemId = response.data[0].id;
                            
                            $.each(response.data, function(i, item) {
                                let isSelected = false;
                                if (job_work_issue_no_id == item.id) {
                                    // Select if value matches (from value attribute or data-selectedJobWorkIssueNo)
                                    isSelected = true;
                                } else if (!job_work_issue_no_id && defaultFirstWarehouse && item.warehouse_name && item.warehouse_name.trim() === defaultFirstWarehouse.trim()) {
                                    // Select item when warehouse name matches data-defaultFirst value
                                    isSelected = true;
                                } else if (!job_work_issue_no_id && !defaultFirstWarehouse && i === 0) {
                                    // Auto-select first option when creating new record (default behavior)
                                    isSelected = true;
                                }
                                
                                if (isSelected) {
                                    options += "<option data-category_id='"+item.product_category_id+"' data-warehouse_id='"+item.warehouse_id+"' data-job_work_issue_no='"+item.job_work_issue_no+"' data-job_work_issue_date='"+item.job_work_issue_date+"' data-vendor_id='"+item.vendor_id+"' value='" + item.id +
                                        "' selected >" + item.job_work_issue_no + "</option>";
                                } else {
                                    options += "<option data-category_id='"+item.product_category_id+"' data-warehouse_id='"+item.warehouse_id+"' data-job_work_issue_no='"+item.job_work_issue_no+"' data-job_work_issue_date='"+item.job_work_issue_date+"' data-vendor_id='"+item.vendor_id+"' value='" + item.id +
                                        "'>" + item.job_work_issue_no + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_job_work_issue_no").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_job_work_issue_no").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_job_work_issue_no").attr('data-append')).empty();
                                $("." + $(".search_by_job_work_issue_no").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_job_work_issue_no").attr('data-append')).empty();
                                $("." + $(".search_by_job_work_issue_no").attr('data-append')).append(options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="job_work_issue_no" class="form-select search_by_job_work_issue_no';
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
                                '<div class="form-group"> <label for="job_work_issue_no">Select Job Work Issue No ';
                            if (is_required) {
                                html += '<span class="text-danger">*</span>';
                            }
                            html += '</label>';
                            html += select_tag;
                            html += '</div></div>';
                            $(".country_div").remove();
                            instance.parent().parent().after(html);
                        } else if (instance.parent("tr")) {
                            $(".search_by_job_work_issue_no").removeClass('d-none').empty().append(options);
                        } else {
                            console.log("getState 82", instance.parent());
                            instance.parent().after(options);
                        }

                        // Reinitialize select2 if it was used
                        if (is_select2) {
                            $('.search_by_job_work_issue_no').select2();
                        }
                        
                        $('.search_by_job_work_issue_no').trigger("change");
                    }
                },
                error: function(xhr, status, error) {
                    jobWorkIssueNoApiLoading = false; // Reset loading flag on error
                    
                    // Hide loading state
                    if (window.ApiLoadingManager) {
                        window.ApiLoadingManager.hideLoading('.search_by_job_work_issue_no');
                    }
                    
                    if (status !== 'abort') {
                        console.error("Job Work Issue No API error:", error);
                        // Optionally show error message to user
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            console.error("Error message:", xhr.responseJSON.message);
                        }
                    }
                },
                complete: function() {
                    jobWorkIssueNoApiRequest = null; // Reset request object
                    if (window.ApiLoadingManager) {
                        window.ApiLoadingManager.unregisterRequest('jobWorkIssueNo');
                    }
                }
            });

            // Register request for tracking
            if (window.ApiLoadingManager) {
                window.ApiLoadingManager.registerRequest('jobWorkIssueNo', jobWorkIssueNoApiRequest);
            }
        } else {
            // Hide loading if company_id is not available
            if (window.ApiLoadingManager) {
                window.ApiLoadingManager.hideLoading('.search_by_job_work_issue_no');
            }
        }
    }
    $('.search_by_company').trigger("change");
</script>

