<script>
    if ($('meta[name="company_id"]').attr('value')) {
        fetch_companyVendors();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            fetch_companyVendors();
        }
    });

    function fetch_companyVendors() {
        let instance = $('.search_by_vendor');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let vendor_id = '';

        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
        }

        if (!vendor_id) {
            vendor_id = instance.attr("data-selectedVendorId");
        }

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-vendors',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Vendor</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {

                                let vendor_name = item.vendor_name || '';
                                let vendor_code = item.vendor_code || '';

                                let vendorText = vendor_code ? vendor_code + ' - ' + vendor_name : vendor_name;

                                if (vendor_id == item.id) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + vendorText + "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        vendorText + "</option>";
                                }
                            });
                        }
                        if ($(".search_by_vendor").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_vendor").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_vendor").attr('data-append')).empty();
                                $("." + $(".search_by_vendor").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_vendor").attr('data-append')).empty();
                                $("." + $(".search_by_vendor").attr('data-append')).append(options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="vendor_id" class="form-select search_by_vendor';
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
                                '<div class="form-group"> <label for="vendor_id">Select Vendor ';
                            if (is_required) {
                                html += '<span class="text-danger">*</span>';
                            }
                            html += '</label>';
                            html += select_tag;
                            html += '</div></div>';
                            $(".country_div").remove();
                            instance.parent().parent().after(html);
                        } else if (instance.parent("tr")) {
                            $(".search_by_vendor").removeClass('d-none').empty().append(options);
                        } else {
                            instance.parent().after(options);
                        }
                        //$('.select2').select2();

                        if ($('.search_by_vendor.select2').hasClass('select2-hidden-accessible')) {
                            $('.search_by_vendor.select2').select2('destroy');
                        }

                        // Wait a moment for DOM to update
                        setTimeout(function() {

                            // Initialize Select2 with templates
                            $('.search_by_vendor.select2').select2({
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
                                        return $('<span><strong style="font-weight: 700; color: #000;">' + code + '</strong> - ' + name + '</span>');
                                    }

                                    return data.text;
                                },
                                templateSelection: function(data) {
                                    if (!data.id) {
                                        return data.text;
                                    }

                                    var text = data.text;
                                    var parts = text.split(' - ');

                                    if (parts.length >= 2) {
                                        var code = parts[0];
                                        var name = parts.slice(1).join(' - ');
                                        return $('<span><strong style="font-weight: 700; color: #000;">' + code + '</strong> - ' + name + '</span>');
                                    }

                                    return data.text;
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

                            // Apply CSS styling after Select2 renders
                            setTimeout(function() {
                                // Force bold styling via CSS manipulation
                                $('.select2-results__option').each(function() {
                                    var text = $(this).text();
                                    if (text.indexOf(' - ') > -1) {
                                        var parts = text.split(' - ');
                                        var code = parts[0];
                                        var name = parts.slice(1).join(' - ');
                                        $(this).html('<strong style="font-weight: 700; color: #000;">' + code + '</strong> - ' + name);
                                    }
                                });
                            }, 200);

                            // Initialize other select2 fields
                             $('.select2').select2();
                        }, 100);
                    }
                }
            });
        }
    }
    $(document).on('select2:open', '.search_by_vendor', function() {
        setTimeout(function() {
            $('.select2-results__option').each(function() {
                var text = $(this).text();
                if (text.indexOf(' - ') > -1 && !$(this).find('strong').length) {
                    var parts = text.split(' - ');
                    var code = parts[0];
                    var name = parts.slice(1).join(' - ');
                    $(this).html('<strong style="font-weight: 700; color: #000;">' + code + '</strong> - ' + name);
                }
            });
        }, 50);
    });
    $('.search_by_company').trigger("change");
</script>
