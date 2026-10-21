<script>
    function applyCompanyProductFilter() {
        let company_id = $(".search_by_company option:selected").val();
        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }
        let filter_category = $('select[name="product_category_id"] option:selected').val();
        if (!filter_category) {
            filter_category = $(".search_by_job_work_issue_no option:selected").attr('data-category_id');
        }
        if (!filter_category) {
            filter_category = $("#product_category_id").val();
        }
        if ($.trim(company_id || '') !== '' && $.trim(filter_category || '') !== '') {
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

    $(document).on('change', '.search_by_product_category', function () {
        applyCompanyProductFilter();
    });

    $(document).on('change', '#customer_id', function () {
        applyCompanyProductFilter();
    });

    $(document).on('select2:select', '#customer_id', function () {
        applyCompanyProductFilter();
    });

    $(document).on('change', '.search_by_job_work_issue_no', function () {
        applyCompanyProductFilter();
    });

    $(document).on('change', '#warehouse_id, .search_by_warehouse', function () {
        applyCompanyProductFilter();
    });

    function fetch_companyProduct() {
        let instance = $('.search_by_product_variant');
        let company_id = $(".search_by_company option:selected").val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');
        let tax_id = '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!tax_id) {
            tax_id = instance.attr("data-selectedTaxId");
        }

        let category_id = $(".search_by_product_category option:selected").val();
        if (!category_id) {
            category_id = $(".search_by_job_work_issue_no option:selected").attr('data-category_id');
        }
        if (!category_id) {
            category_id = $("#product_category_id").val();
        }

        if (company_id && category_id) {
            let apiData = {
                company_id: company_id,
                category_id: category_id,
                customer_id: $('#customer_id').val() || null,
                product_combo: true,
            };

            let config = window.getProductAndVariantConfig || {};
            if (config.available_stock === true) {
                let warehouse_id = $('#warehouse_id').val();
                if (!warehouse_id) {
                    warehouse_id = $('.search_by_warehouse option:selected').val();
                }

                if (warehouse_id) {
                    apiData.available_stock = true;
                    apiData.warehouse_id = warehouse_id;
                }
            }

            $.ajax({
                type: 'POST',
                url: "{{ route('get-product-and-variant') }}",
                'beforeSend': function (request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: apiData,
                success: function (response) {
                    if (response.status) {
                        let options = "<option value=''>Select Product</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function (i, item) {
                                let productDisplay = item.product || item.product_name || '';
                                let partNo = item.part_no || '';
                                let customerItemCode = item.customer_item_code || item.customer_code || '';
                                let customerItemDesc = item.customer_item_description || '';
                                let is_combo = item.is_combo;

                                let customerParts = [];
                                if (customerItemCode && customerItemCode !== partNo) {
                                    customerParts.push(customerItemCode);
                                }
                                if (customerItemDesc && customerItemDesc !== productDisplay && !customerParts.includes(customerItemDesc)) {
                                    customerParts.push(customerItemDesc);
                                }
                                let customerInfoText = customerParts.join(' ');

                                let productText = partNo ? `${partNo} - ${productDisplay}` : productDisplay;
                                if (customerInfoText) {
                                    productText += ` (${customerInfoText})`;
                                }

                                if (item.available_stock !== undefined && item.available_stock !== null) {
                                    productText += ` (Stock: ${parseFloat(item.available_stock).toFixed(2)})`;
                                }

                                let optionAttributes = '';
                                if (item.available_stock !== undefined && item.available_stock !== null) {
                                    optionAttributes += ` data-available-stock="${item.available_stock}"`;
                                }
                                optionAttributes += ` data-customer_item_code="${customerItemCode}"`;
                                optionAttributes += ` data-customer_item_description="${customerItemDesc}"`;
                                optionAttributes += ` data-customer_info="${customerInfoText}"`;

                                if (tax_id == item.id) {
                                    options += "<option value='" + item.id + "'" + optionAttributes + "  data-product_combo='" + is_combo + "'  data-part_no='" + partNo + "' data-product_name='" + productDisplay + "' selected>" +
                                        productText + "</option>";
                                } else {
                                    options += "<option value='" + item.id + "'" + optionAttributes + " data-product_combo='" + is_combo + "'  data-part_no='" + partNo + "' data-product_name='" + productDisplay + "'>" + productText +
                                        "</option>";
                                }
                            });
                        }

                        if ($(".search_by_product_variant").attr('data-append')) {
                            if ($(document).find("." + $(".search_by_product_variant").attr('data-append')).length > 0) {
                                $("." + $(".search_by_product_variant").attr('data-append')).empty();
                                $("." + $(".search_by_product_variant").attr('data-append')).append(options);
                            } else {
                                $("." + $(".search_by_product_variant").attr('data-append')).empty();
                                $("." + $(".search_by_product_variant").attr('data-append')).append(options);
                            }
                        } else if (instance.parent().parent().hasClass("col-md-6") || instance.parent().parent().hasClass("col-md-4") || instance.parent().parent().hasClass("col-md-3")) {
                            let select_tag = '<select name="product_variant_id" class="form-select search_by_product_variant';
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
                            html += '<div class="form-group"> <label for="product_variant_id">Select Product Variant ';
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

                        setTimeout(function () {
                            if (!$('#custom-select2-bold-style').length) {
                                $('<style id="custom-select2-bold-style">')
                                    .text('.select2-selection__rendered strong { font-weight: 700 !important; color: #000 !important; }')
                                    .appendTo('head');
                            }

                            // ===== FLEXIBLE MATCHER FUNCTION =====
                            var flexibleMatcher = function (params, data) {
                                if ($.trim(params.term) === '') {
                                    return data;
                                }

                                if (typeof data.text === 'undefined' || data.text === null) {
                                    return null;
                                }

                                var searchTerm = params.term.toLowerCase().trim();
                                var productText = data.text.toLowerCase();

                                // Remove HTML if present
                                var tempDiv = document.createElement('div');
                                tempDiv.innerHTML = productText;
                                productText = (tempDiv.textContent || tempDiv.innerText || productText).toLowerCase();

                                // Extract part number (before " - ") for more accurate matching
                                var partNumber = '';
                                var dashIndex = productText.indexOf(' - ');
                                if (dashIndex > -1) {
                                    partNumber = productText.substring(0, dashIndex).trim();
                                }

                                // Priority 1: Exact match in part number
                                if (partNumber && partNumber === searchTerm) {
                                    return data;
                                }

                                // Priority 2: Part number starts with search term
                                if (partNumber && partNumber.indexOf(searchTerm) === 0) {
                                    return data;
                                }

                                // Priority 3: Search term appears as complete substring in part number
                                if (partNumber && partNumber.indexOf(searchTerm) > -1) {
                                    return data;
                                }

                                // Priority 4: All words from search term must appear in part number (any order)
                                var words = searchTerm.split(/\s+/).filter(function (w) { return w.length > 0; });

                                if (words.length > 0 && partNumber) {
                                    var allWordsInPartNumber = true;
                                    for (var i = 0; i < words.length; i++) {
                                        if (partNumber.indexOf(words[i]) === -1) {
                                            allWordsInPartNumber = false;
                                            break;
                                        }
                                    }

                                    if (allWordsInPartNumber) {
                                        return data;
                                    }
                                }

                                // Priority 5: All words match in product name (after part number) - any order
                                var productName = '';
                                if (dashIndex > -1) {
                                    productName = productText.substring(dashIndex + 3).trim(); // Skip " - "
                                }

                                if (words.length > 0 && productName) {
                                    var allWordsInName = true;
                                    for (var i = 0; i < words.length; i++) {
                                        if (productName.indexOf(words[i]) === -1) {
                                            allWordsInName = false;
                                            break;
                                        }
                                    }

                                    if (allWordsInName) {
                                        return data;
                                    }
                                }

                                // Priority 6: All words match anywhere in the full product text (any order)
                                if (words.length > 0) {
                                    var allWordsInText = true;
                                    for (var i = 0; i < words.length; i++) {
                                        if (productText.indexOf(words[i]) === -1) {
                                            allWordsInText = false;
                                            break;
                                        }
                                    }

                                    if (allWordsInText) {
                                        return data;
                                    }
                                }

                                // No match found
                                return null;
                            };

                            // ===== INITIALIZE ALL SELECT2 WITH FLEXIBLE MATCHER =====
                            $('.search_by_product_variant.select2, .select2').each(function () {
                                var $elem = $(this);

                                // Destroy if already initialized
                                if ($elem.hasClass('select2-hidden-accessible')) {
                                    $elem.select2('destroy');
                                }

                                // Initialize with flexible matcher
                                $elem.select2({
                                    placeholder: $elem.attr('placeholder') || "Type to search...",
                                    allowClear: true,
                                    minimumInputLength: 0,
                                    width: '100%',
                                    escapeMarkup: function (markup) {
                                        return markup;
                                    },
                                    templateResult: function (data) {
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
                                        return $('<span>' + data.text + '</span>');
                                    },
                                    templateSelection: function (data) {
                                        if (!data.id) {
                                            return data.text;
                                        }
                                        var text = data.text;
                                        var tempDiv = $('<div>').html(text);
                                        var cleanText = tempDiv.text();
                                        var parts = cleanText.split(' - ');
                                        if (parts.length >= 2) {
                                            var code = parts[0];
                                            var rest = parts.slice(1).join(' - ');
                                            return $('<span><strong style="font-weight: 700; color: #000;">' + code + '</strong> - ' + rest + '</span>');
                                        }
                                        return $('<span>' + cleanText + '</span>');
                                    },
                                    language: {
                                        noResults: function () {
                                            return "No results found";
                                        },
                                        searching: function () {
                                            return "Searching...";
                                        }
                                    },
                                    matcher: flexibleMatcher
                                });
                            });

                        }, 100);

                        $('.search_by_product_variant.select2').on('select2:select', function (e) {
                            setTimeout(function () {
                                formatSelectedProduct();
                            }, 10);
                        });

                        setTimeout(function () {
                            formatSelectedProduct();
                        }, 200);
                    }
                }
            });
        }
    }

    function formatSelectedProduct() {
        $('.search_by_product_variant.select2').each(function () {
            var $select = $(this);
            var $container = $select.next('.select2-container').find('.select2-selection__rendered');

            if ($container.length) {
                var text = $container.text();

                if (text && text.indexOf(' - ') > -1) {
                    var parts = text.split(' - ');
                    var code = parts[0];
                    var rest = parts.slice(1).join(' - ');
                    $container.html('<strong style="font-weight: 700 !important; color: #000 !important;">' + code + '</strong> - ' + rest);
                }
            }
        });
    }

    $(document).on('select2:open', '.search_by_product_variant, .select2', function () {
        setTimeout(function () {
            applyProductBoldFormatting();
        }, 50);
    });

    $(document).on('keyup', '.select2-search__field', function () {
        setTimeout(function () {
            applyProductBoldFormatting();
        }, 10);
    });

    function applyProductBoldFormatting() {
        $('.select2-results__option').each(function () {
            if ($(this).find('strong').length > 0) {
                return;
            }

            var text = $(this).text();
            if (text.indexOf(' - ') > -1) {
                var parts = text.split(' - ');
                var code = parts[0];
                var rest = parts.slice(1).join(' - ');
                var formattedRest = rest.replace(/\(([^)]+)\)/g, function(m, p1) {
                    if (p1.toLowerCase().indexOf('stock:') === 0) {
                        return '<span style="color: #6c757d;">(' + p1 + ')</span>';
                    }
                    return '<span style="color: #0d6efd; font-weight: 600;">(' + p1 + ')</span>';
                });
                $(this).html('<strong style="font-weight: 700; color: #000;">' + code + '</strong> - ' + formattedRest);
            }
        });
    }

    $('.search_by_company').trigger("change");
</script>