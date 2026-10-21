<script>
    let warehouseApiRequest = null; // Declare globally, outside the event
    let warehouseApiLoading = false; // Flag to track if request is in progress

    if ($('meta[name="company_id"]').attr('value')) {
        fetch_warehouse();
    }

    $(document).on('change', '.search_by_company', function() {
        fetch_warehouse();

    });

    function fetch_warehouse() {
        // Prevent duplicate calls if request is already in progress
        if (warehouseApiLoading) {
            return;
        }

        let instance = $('.search_by_warehouse');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let warehouse_id = '';
        let defaultFirst = instance.attr('data-defaultFirst');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }

        if (!warehouse_id) {
            warehouse_id = instance.attr('data-selectedWarehouseId');
        }

        if (company_id) {
            // Abort previous request if any
            if (warehouseApiRequest !== null) {
                warehouseApiRequest.abort();
            }

            // Show loading state
            warehouseApiLoading = true;
            if (window.ApiLoadingManager) {
                window.ApiLoadingManager.showLoading('.search_by_warehouse', {
                    showGlobalLoader: false, // Global loader handled by ajaxStart/ajaxStop
                    showElementLoader: true,
                    disableElement: true
                });
            }

            warehouseApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-warehouse') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    warehouseApiLoading = false; // Reset loading flag on success

                    // Hide loading state
                    if (window.ApiLoadingManager) {
                        window.ApiLoadingManager.hideLoading('.search_by_warehouse');
                    }


                    if (response.status) {
                        let options = "<option value=''>Select Warehouse</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                let isSelected = false;
                                // Check if warehouse_id is set and matches
                                if (warehouse_id && warehouse_id == item.id) {
                                    isSelected = true;
                                }
                                // If warehouse_id is empty/undefined/null, use defaultFirst
                                else if ((!warehouse_id || warehouse_id === '' || warehouse_id === 'undefined' || warehouse_id === null) && defaultFirst) {

                                    let defaultFirstLower = String(defaultFirst || '').toLowerCase().trim();
                                    let itemWarehouseNameLower = String(item?.warehouse || '').toLowerCase().trim();

                                    // console.log("defaultFirst L-59", defaultFirst, defaultFirstLower, itemWarehouseNameLower);

                                    if (item?.id == defaultFirst || itemWarehouseNameLower == defaultFirstLower) {
                                        isSelected = true;
                                    }
                                }

                                if (isSelected) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.warehouse + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.warehouse + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_warehouse").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_warehouse").attr(
                                    'data-append'))
                                .length > 0) {
                                $("." + $(".search_by_warehouse").attr('data-append')).empty();
                                $("." + $(".search_by_warehouse").attr('data-append')).append(
                                    options);
                            } else {
                                $("." + $(".search_by_warehouse").attr('data-append')).empty();
                                $("." + $(".search_by_warehouse").attr('data-append')).append(
                                    options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="warehouse_id" class="form-select search_by_warehouse';
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
                                '<div class="form-group"> <label for="warehouse_id">Select Warehouse ';
                            if (is_required) {
                                html += '<span class="text-danger">*</span>';
                            }
                            html += '</label>';
                            html += select_tag;
                            html += '</div></div>';
                            $(".country_div").remove();
                            instance.parent().parent().after(html);
                        } else if (instance.parent("tr")) {
                            // console.log("getState 39", instance, instance.parent(), instance.next("th"));
                            $(".search_by_area").removeClass('d-none').empty().append(options);
                            /*
                            // $(".search_by_country").remove();
                            // instance.parent().next("th").remove();
                            // instance.parent().after('<th>'+options+'</th>');
                            */
                        } else {
                            console.log("getState 82", instance.parent());
                            instance.parent().after(options);
                        }

                        // Reinitialize select2 if it was used
                        if (is_select2) {
                            $('.search_by_warehouse').select2();
                        }

                        // $('.search_by_state').trigger("change");
                    }
                },
                error: function(xhr, status, error) {
                    warehouseApiLoading = false; // Reset loading flag on error

                    // Hide loading state
                    if (window.ApiLoadingManager) {
                        window.ApiLoadingManager.hideLoading('.search_by_warehouse');
                    }

                    if (status !== 'abort') {
                        console.error("Warehouse API error:", error);
                        // Optionally show error message to user
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            console.error("Error message:", xhr.responseJSON.message);
                        }
                    }
                },
                complete: function() {
                    warehouseApiRequest = null; // Reset request object
                    if (window.ApiLoadingManager) {
                        window.ApiLoadingManager.unregisterRequest('warehouse');
                    }
                }
            });

            // Register request for tracking
            if (window.ApiLoadingManager) {
                window.ApiLoadingManager.registerRequest('warehouse', warehouseApiRequest);
            }
        } else {
            // Hide loading if company_id is not available
            if (window.ApiLoadingManager) {
                window.ApiLoadingManager.hideLoading('.search_by_warehouse');
            }
        }
    }
    $('.search_by_company').trigger("change");
</script>
