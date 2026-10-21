<script>
    function applyCompanyProductFilter() {
        let company_id = $(".search_by_company option:selected").val();
        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        if ($.trim(company_id || '') !== '') {
            fetch_companyProduct();
        }
    }

    if ($('meta[name="company_id"]').attr('value')) {
        filter_company = $('meta[name="company_id"]').attr('value');
        if ($.trim(filter_company || '') !== '') {
            applyCompanyProductFilter();
        }
    }

    $(document).on('change', '.search_by_company', function () {
        filter_company = $('select[name="company_id"] option:selected').val();
        if ($.trim(filter_company || '') !== '') {
            applyCompanyProductFilter();
        }
    });

    function fetch_companyProduct() {
        let instance = $('.search_by_product_variant');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            $.ajax({
                type: 'POST',
                url: "{{ route('get-product-and-variant-without-category') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {

                        let options = "<option value=''>Select Product</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                let productDisplay = item.product || item.product_name || '';
                                let productItemCode = item.part_no || '';
                                let customerItemCode = item.customer_item_code || item.customer_code || '';
                                let customerItemDesc = item.customer_item_description || '';

                                let customerParts = [];
                                if (customerItemCode && customerItemCode !== productItemCode) {
                                    customerParts.push(customerItemCode);
                                }
                                if (customerItemDesc && customerItemDesc !== productDisplay && !customerParts.includes(customerItemDesc)) {
                                    customerParts.push(customerItemDesc);
                                }
                                let customerInfoText = customerParts.join(' ');

                                let productText = productItemCode ? `${productItemCode} - ${productDisplay}` : productDisplay;
                                if (customerInfoText) {
                                    productText += ` (${customerInfoText})`;
                                }

                                options += "<option value='" + item.id + "' data-part_no='" + productItemCode + "' data-product_name='" + productDisplay + "' data-customer_item_code='" + customerItemCode + "' data-customer_item_description='" + customerItemDesc + "' data-customer_info='" + customerInfoText + "'>" + productText +
                                        "</option>";
                            });
                        }

                        if ($(".search_by_product_variant").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_product_variant").attr('data-append'))
                                .length > 0) {
                                $("." + $(".search_by_product_variant").attr('data-append')).empty();
                                $("." + $(".search_by_product_variant").attr('data-append')).append(
                                options);
                            } else {
                                $("." + $(".search_by_product_variant").attr('data-append')).empty();
                                $("." + $(".search_by_product_variant").attr('data-append')).append(
                                options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance
                            .parent().parent().hasClass("col-md-4") || instance.parent().parent()
                            .hasClass("col-md-3")) {

                            let select_tag =
                                '<select name="product_variant_id" class="form-select search_by_product_variant';
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
                                '<div class="form-group"> <label for="product_variant_id">Select Product Variant ';
                            if (is_required) {
                                html += '<span class="text-danger">*</span>';
                            }
                            html += '</label>';
                            html += select_tag;
                            html += '</div></div>';
                            $(".country_div").remove();
                            instance.parent().parent().after(html);
                        } else if (instance.parent("tr")) {
                            $(".search_by_product_variant").removeClass('d-none').empty().append(options);

                        } else {
                            instance.parent().after(options);
                        }
                        if ($('.search_by_product_variant.select2').hasClass('select2-hidden-accessible')) {
                            $('.search_by_product_variant.select2').select2('destroy');
                        }

                        // Wait a moment for DOM to update
                        setTimeout(function() {

                            // Initialize Select2 with templates
                            $('.search_by_product_variant.select2').select2({
                                placeholder: "Type to search Product...",
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

                                    var $option = $(data.element);
                                    var custInfo = $option.attr('data-customer_info') || $option.data('customer_info') || '';
                                    var text = data.text;
                                    var parts = text.split(' - ');

                                    if (parts.length >= 2) {
                                        var code = parts[0];
                                        var rest = parts.slice(1).join(' - ');

                                        // Stock format in gray
                                        rest = rest.replace(/\(Stock:\s*[^)]+\)/gi, function(m) {
                                            return '<span style="color: #6c757d;">' + m + '</span>';
                                        });

                                        // Only highlight actual customer item code / info in blue
                                        if (custInfo) {
                                            var custEscaped = custInfo.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                                            var custRegex = new RegExp('\\(' + custEscaped + '\\)', 'g');
                                            rest = rest.replace(custRegex, '<span style="color: #0d6efd; font-weight: 600;">(' + custInfo + ')</span>');
                                        }

                                        return $('<span><strong style="font-weight: 700; color: #000;">' + code + '</strong> - ' + rest + '</span>');
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
                                        var rest = parts.slice(1).join(' - ');
                                        return $('<span><strong style="font-weight: 700; color: #000;">' + code + '</strong> - ' + rest + '</span>');
                                    }

                                    return data.text;
                                },
                                language: {
                                    noResults: function() {
                                        return "No Product found";
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

    $(document).on('select2:open', '.search_by_product_variant', function() {
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
