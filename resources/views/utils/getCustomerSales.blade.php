<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companyCustomer();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            fetch_companyCustomer();
        }
    });

    $(document).on('change', '.search_by_customer_type', function () {
        if ($(".search_by_customer_type option:selected").val() != null && $(".search_by_customer_type option:selected").val() !== "") {
            fetch_companyCustomer();
        }
    });

    function fetch_companyCustomer() {
        let instance = $('.search_by_customer');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let customer_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }

        if (!customer_id) {
            customer_id = instance.attr("data-selectedCustomerId");
        }

        let customer_type_id = $(".search_by_customer_type option:selected").val();
        let data = {
            company_id: company_id,
        };

        if (customer_type_id) {
            data.customer_type_id = customer_type_id;
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-customer',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: data,
                success: function(response) {

                    if (response.status) {
                        let options = "<option value=''>Select Customer</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                let customer_name = item.customer_name || '';
                                let client_code = item.client_code || '';

                                let customerText = client_code ? client_code + ' - ' + customer_name : customer_name;

                                if (customer_id == item.id) {
                                    options += '<option value="' + item.id + '" selected>' + customerText + '</option>';
                                } else {
                                    options += '<option value="' + item.id + '">' + customerText + '</option>';
                                }
                            });
                        }

                        if ($(".search_by_customer").attr('data-append')) {
                            let appendClass = $(".search_by_customer").attr('data-append');
                            $("." + appendClass).empty().append(options);
                        }

                        // Destroy existing Select2 instance
                        if ($('.search_by_customer.select2').hasClass('select2-hidden-accessible')) {
                            $('.search_by_customer.select2').select2('destroy');
                        }

                        // Wait a moment for DOM to update
                        setTimeout(function() {

                            // Add custom CSS for bold codes in selection
                            if (!$('#custom-select2-bold-style').length) {
                                $('<style id="custom-select2-bold-style">')
                                    .text('.select2-selection__rendered strong { font-weight: 700 !important; color: #000 !important; }')
                                    .appendTo('head');
                            }

                            // Initialize Select2 with templates
                            $('.search_by_customer.select2').select2({
                                placeholder: "Type to search customer...",
                                allowClear: true,
                                minimumInputLength: 0,
                                width: '100%',
                                escapeMarkup: function(markup) {
                                    return markup;
                                },
                                templateResult: function(data) {
                                    if (!data.id) {
                                        return data.text;
                                    }

                                    var text = data.text;
                                    var parts = text.split(' - ');

                                    if (parts.length >= 2) {
                                        var code = parts[0];
                                        var name = parts.slice(1).join(' - ');
                                        return '<span><strong style="font-weight: 700 !important; color: #000 !important;">' + code + '</strong> - ' + name + '</span>';
                                    }

                                    return '<span>' + data.text + '</span>';
                                },
                                templateSelection: function(data) {
                                    if (!data.id) {
                                        return data.text;
                                    }

                                    var text = data.text;

                                    // Remove any existing HTML tags first
                                    var tempDiv = $('<div>').html(text);
                                    var cleanText = tempDiv.text();

                                    var parts = cleanText.split(' - ');

                                    if (parts.length >= 2) {
                                        var code = parts[0];
                                        var name = parts.slice(1).join(' - ');
                                        return '<span><strong style="font-weight: 700 !important; color: #000 !important;">' + code + '</strong> - ' + name + '</span>';
                                    }

                                    return '<span>' + cleanText + '</span>';
                                },
                                language: {
                                    noResults: function() {
                                        return "No customers found";
                                    },
                                    searching: function() {
                                        return "Searching...";
                                    }
                                },
                                matcher: function(params, data) {
                                    if ($.trim(params.term) === '') {
                                        return data;
                                    }

                                    var term = params.term.toLowerCase();
                                    var customerName = data.text.toLowerCase();

                                    var terms = term.split(' ');
                                    var matches = true;

                                    for (var i = 0; i < terms.length; i++) {
                                        if (customerName.indexOf(terms[i]) === -1) {
                                            matches = false;
                                            break;
                                        }
                                    }

                                    return matches ? data : null;
                                }
                            });

                            // Initialize other select2 fields
                            $('.select2').select2();

                        }, 100);

                        // Apply bold formatting to selected item after selection
                        $('.search_by_customer.select2').on('select2:select', function (e) {
                            setTimeout(function() {
                                formatSelectedCustomer();
                            }, 10);
                        });

                        // Also format on page load if value exists
                        setTimeout(function() {
                            formatSelectedCustomer();
                        }, 200);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }
    }

    // Function to format the selected customer display
    function formatSelectedCustomer() {
        $('.search_by_customer.select2').each(function() {
            var $select = $(this);
            var $container = $select.next('.select2-container').find('.select2-selection__rendered');

            if ($container.length) {
                var text = $container.text();

                if (text && text.indexOf(' - ') > -1) {
                    var parts = text.split(' - ');
                    var code = parts[0];
                    var name = parts.slice(1).join(' - ');
                    $container.html('<strong style="font-weight: 700 !important; color: #000 !important;">' + code + '</strong> - ' + name);
                }
            }
        });
    }

    // Handle rendering when dropdown opens or when searching
    $(document).on('select2:open', '.search_by_customer', function() {
        // This ensures formatting is applied when dropdown opens
        setTimeout(function() {
            applyBoldFormatting();
        }, 50);
    });

    // Handle rendering during search/typing
    $(document).on('keyup', '.select2-search__field', function() {
        // Apply formatting after each keystroke
        setTimeout(function() {
            applyBoldFormatting();
        }, 10);
    });

    // Function to apply bold formatting to dropdown results
    function applyBoldFormatting() {
        $('.select2-results__option').each(function() {
            // Check if already formatted to avoid re-formatting
            if ($(this).find('strong').length > 0) {
                return;
            }

            var text = $(this).text();
            if (text.indexOf(' - ') > -1) {
                var parts = text.split(' - ');
                var code = parts[0];
                var name = parts.slice(1).join(' - ');
                $(this).html('<strong style="font-weight: 700; color: #000;">' + code + '</strong> - ' + name);
            }
        });
    }

    $('.search_by_company').trigger("change");
</script>
