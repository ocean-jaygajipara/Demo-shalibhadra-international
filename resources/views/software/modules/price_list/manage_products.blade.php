@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
@endphp

@section('title', $page_title)

@section('content')
   <div class="px-1">
    <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                ['title' => (isset($edit) && $edit?->id) ? "Edit ".$page_title : "Create ".$page_title , 'url' => ''],
            ],
        ])
        <a class="btn btn-primary waves-effect waves-light text-white" href="{{ url()->previous() }}">
            <i class="menu-icon ti ti-chevrons-left"></i> Back
        </a>
    </div>
</div>
    <div class="card my-3 mb-4">
        <div class="card-header">
            <h5 class="mb-2">Use filters to see products</h5>
        </div>
        <div class="card-body">
            <form id="price_list_products_form"
                method="POST" enctype="multipart/form-data">

                <input type="hidden" id="is_view_product" name="is_view_product" value="0">
                <input type="hidden" name="price_list_id" id="price_list_id" value="{{ isset($priceList) && $priceList?->id ? $priceList?->id : '' }}">
                <input type="hidden" name="company_id" id="company_id" value="{{ isset($priceList) && $priceList?->company_id ? $priceList?->company_id : '' }}">
                @csrf

                <div class="row">
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Product Category</label>
                            <select name="product_category_id" id="product_category_id"
                                class="form-control @error('product_category_id') is-invalid @enderror search_by_product_category select2"
                                data-append="search_by_product_category"
                                data-selectedCategoryId="{{ isset($edit) && $edit?->product_category_id ? $edit?->product_category_id : '' }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('product_category_id', $edit->product_category_id ?? '') ? '' : 'selected' }}>
                                    Select Product Category
                                </option>
                            </select>
                            @error('product_category_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Product Sub Category</label>
                            <select name="subcategory_id" id="subcategory_id"
                                class="form-control @error('subcategory_id') is-invalid @enderror search_by_product_subcategory select2"
                                data-append="search_by_product_subcategory"
                                data-selectedSubcategoryId="{{ isset($edit) && $edit?->subcategory_id ? $edit?->subcategory_id : '' }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('subcategory_id', $edit->subcategory_id ?? '') ? '' : 'selected' }}>
                                    Select Product Subcategory
                                </option>
                            </select>
                            @error('subcategory_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Enter Discount (in %) <span class="text-danger">*</span></label>
                            <input id="input_discount_percentage" type="text" class="form-control @error('input_discount_percentage') is-invalid @enderror"
                                name="input_discount_percentage" value="{{ old('input_discount_percentage', $edit->input_discount_percentage ?? '') }}" autocomplete="discount"
                                placeholder="Enter Discount (in %)" onkeypress="return isDecimal(event)">
                            @error('input_discount_percentage')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12 mt-3">
                        <button type="button" class="btn btn-primary mt-1 mb-1" id="apply"> Apply </button>
                        <button type="button" class="btn btn-primary mt-1 mb-1" id="view_products"> View Products </button>
                        <button type="button" class="btn btn-primary mt-1 mb-1 final_save_btn" id="save_up_btn" style="display: none !important;"> Save </button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger mt-1 mb-1">Cancel</a>
                    </div>

                    <div class="divider">
                        <hr />
                    </div>

                    <div class="col-md-12">
                        <table id="product_list" class="table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Category > Subcategory</th>
                                    <th>Product Name</th>
                                    <th>Price</th>
                                    <th>Discounted Selling Price</th>
                                    <th>Discounted Value</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="product_list_body">

                            </tbody>
                        </table>
                    </div>

                    <div class="col-md-12">
                        <button type="button" id="save_up_btn" class="final_save_btn btn btn-primary mt-1 mb-1" style="display: none !important;">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script>
        function save() {
            var isViewProduct = $('#is_view_product').val();
            var isValid = true;
            if(isViewProduct == 0) {
                isValid = false;
                toastr.error('Please get products first');
                return;
            }

            // $('input[name="discounted_selling_price[]"]').each(function() {
            //     if ($.trim($(this).val()) === '') {
            //         isValid = false;
            //         $(this).addClass('is-invalid');
            //     } else {
            //         $(this).removeClass('is-invalid');
            //     }
            // });

            if(isValid === true) {
                $('#save_up_btn').prop('disabled', true);
                $('#save_up_btn').text('Processing...');

                $.ajax({
                    url: "{{ route($route.'.save-pricelist-products') }}",
                    type: 'POST',
                    data: $('#price_list_products_form').serialize(),
                    success: function(response) {
                        $('#save_up_btn').prop('disabled', false);
                        $('#save_up_btn').text('Save');
                        toastr.success('Price list saved successfully');
                        window.location.href = "{{ route($route.'.index') }}";
                    }
                });
            }
        }

        $(document).ready(function() {
            let company_id = $('#company_id').val();
            $(".final_save_btn").hide();

            $.ajax({
                type: 'POST',
                url: "{{ env('API_URL') }}product/category/list",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Product Category</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                options += "<option value='" + item.id + "'>" + item.name + "</option>";
                            });
                        }
                        $(".search_by_product_category").empty();
                        $(".search_by_product_category").append(options);
                        $('.select2').select2();
                    }
                }
            });

            $(document).on('change', '.search_by_product_category', function() {
                let company_id = $('#company_id').val();
                $(".final_save_btn").hide();
                let category_id = $(this).val();

                $.ajax({
                    type: 'POST',
                    url: "{{ env('API_URL') }}product/sub-category/list",
                    'beforeSend': function(request) {
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                            'content'));
                    },
                    data: {
                        company_id: company_id,
                        product_category_id: category_id,
                    },
                    success: function(response) {
                        if (response.status) {
                            let options = "<option value=''>Select Product Subcategory</option>";
                            if (response.data && response.data.length > 0) {
                                $.each(response.data, function(i, item) {
                                    options += "<option value='" + item.id + "'>" + item.name + "</option>";
                                });
                            }
                            $(".search_by_product_subcategory").empty();
                            $(".search_by_product_subcategory").append(options);
                            $('.select2').select2();
                        }
                    }
                });
            });

            $('#view_products').click(function() {
                var category_id = $('#product_category_id').val();
                var subcategory_id = $('#subcategory_id').val();
                var price_list_id = $('#price_list_id').val();
                var isValid = true;

                if (category_id === '') {
                    toastr.error('Please select category');
                    isValid = false;
                }

                if (subcategory_id === '' || subcategory_id === null) {
                    toastr.error('Please select subcategory');
                    isValid = false;
                }

                if(isValid === true) {
                    $('#product_list').show();
                    $('#product_list_body').html('');
                    $(".final_save_btn").show();
                    $(this).prop('disabled', true);
                    $(this).text('Processing...');

                    $.ajax({
                        url: "{{ route($route . '.get-products') }}",
                        type: 'POST',
                        data: {
                            category_id: category_id,
                            subcategory_id: subcategory_id,
                            price_list_id: price_list_id,
                            _token: '{{ csrf_token() }}'
                        },
                        dataType: 'json',
                        success: function(response) {
                            $(this).text('View Products');
                            $('#is_view_product').val(1);
                            $('#view_products').prop('disabled', false);
                            $('#view_products').text('View Products');
                            var category_name = $('#product_category_id option:selected').text();
                            var subcategory_name = $('#subcategory_id option:selected').text();

                            $.each(response, function(index, value) {
                                let price = parseFloat(value.price);
                                let tax = parseFloat(value.tax_value);
                                let includingGst = parseInt(value.including_gst);

                                let basePrice = price;
                                let gstAmount = 0;

                                if (includingGst === 1 && tax > 0) {
                                    basePrice = price / (1 + (tax / 100));
                                    gstAmount = price - basePrice;
                                }

                                basePrice = basePrice.toFixed(2);
                                gstAmount = gstAmount.toFixed(2);

                                let basePriceText = '';
                                if (includingGst === 1 && tax > 0) {
                                    basePriceText += '<span><strong>Include: </strong>'+basePrice+' ('+value.tax_name+')</span>';
                                }

                                $('#product_list_body').append(`
                                    <tr>
                                        <td>
                                        ${++index}
                                        <input type="hidden" name="product_id[]" value="${value.id}">
                                        <input type="hidden" name="product_variant_price_id[]" value="${value.product_variant_price_id}">
                                        </td>
                                        <td>${category_name} <br/> ${subcategory_name}</td>
                                        <td><span class="fw-bold">Name: </span>${value.product_name}<br/><span class="fw-bold"> Code: </span>${value.product_code}</td>
                                        <td>
                                            ${value.price}
                                            <input type="hidden" name="price[]" value="${basePrice}"><br/>
                                            ${basePriceText}
                                        </td>
                                        <td><input onkeypress="return isDecimal(event)" type="text" class="form-control" name="discounted_selling_price[]" id="discount_price" value="`+(value.discounted_selling_price ? value.discounted_selling_price : '')+`"></td>
                                        <td>
                                            <span class="discount_info"><span class="fw-bold">Discount Amount: </span>`+(value.discounted_amount ? value.discounted_amount : '')+`<br/><span class="fw-bold"> Discount Percentage: </span>`+(value.discount_percentage_value ? value.discount_percentage_value + `%` : '')+`</span>
                                            <input type="hidden" class="form-control" name="discounted_amount[]" id="discount_value" value="`+(value.discounted_amount ? value.discounted_amount : '')+`">
                                            <input type="hidden" name="discount_percentage_value[]" id="discount_value_percentage" value="`+(value.discount_percentage_value ? value.discount_percentage_value : '')+`">
                                        </td>
                                        <td>
                                            <a href="javascript:void(0)" class="btn btn-primary btn-sm net_discount_btn">Net Discount</a>
                                            <a href="javascript:void(0)" class="btn btn-danger btn-sm clear_discount_btn">Clear</a>
                                        </td>
                                    </tr>
                                `);
                            });
                        }
                    });
                }
            });

            // $(document).on('click', '.net_discount_btn', function() {
            //     var discountedSellingPrice = parseFloat($(this).closest('tr').find('input[name="discounted_selling_price[]"]').val());
            //     var discountedValue = parseFloat($(this).closest('tr').find('input[name="discounted_amount[]"]').val());
            //     var price = parseFloat($(this).closest('tr').find('input[name="price[]"]').val());
            //     var netDiscount = price - discountedSellingPrice;
            //     var discountedValuePercentage = (netDiscount / price) * 100;

            //     if(discountedSellingPrice > price) {
            //         toastr.error("Discounted selling price cannot be greater than price", "Validation Error");
            //         return;
            //     }

            //     $(this).closest('tr').find('input[name="discounted_amount[]"]').val(netDiscount.toFixed(2));
            //     $(this).closest('tr').find('input[name="discounted_selling_price[]"]').val(discountedSellingPrice.toFixed(2));
            //     $(this).closest('tr').find('input[name="discount_percentage_value[]"]').val(discountedValuePercentage.toFixed(2));

            //     $(this).closest('tr').find('.discount_info').html(`<span class="fw-bold">Discount Amount: </span>`+(netDiscount.toFixed(2) ? netDiscount.toFixed(2) : '')+`<br/><span class="fw-bold"> Discount Percentage: </span>`+(discountedValuePercentage.toFixed(2) ? discountedValuePercentage.toFixed(2) + `%` : ''));
            // });

            $(document).on('click', '.net_discount_btn', function() {
                var $row = $(this).closest('tr');
                var discountedSellingPrice = parseFloat($row.find('input[name="discounted_selling_price[]"]').val());
                var price = parseFloat($row.find('input[name="price[]"]').val());

                // If not a number, default to 0
                if (isNaN(discountedSellingPrice)) discountedSellingPrice = 0;
                if (isNaN(price)) price = 0;

                // Validate
                if (discountedSellingPrice > price) {
                    toastr.error("Discounted selling price cannot be greater than price", "Validation Error");
                    return;
                }

                // Handle zero case
                var netDiscount = 0;
                var discountedValuePercentage = 0;

                if (discountedSellingPrice === 0) {
                    netDiscount = 0; // Full discount
                    discountedValuePercentage = 0;
                } else {
                    netDiscount = price - discountedSellingPrice;
                    discountedValuePercentage = (netDiscount / price) * 100;
                }

                // Set values
                $row.find('input[name="discounted_amount[]"]').val(netDiscount.toFixed(2));
                $row.find('input[name="discounted_selling_price[]"]').val(discountedSellingPrice.toFixed(2));
                $row.find('input[name="discount_percentage_value[]"]').val(discountedValuePercentage.toFixed(2));

                $row.find('.discount_info').html(
                    `<span class="fw-bold">Discount Amount: </span>${netDiscount.toFixed(2)}<br/>
                    <span class="fw-bold">Discount Percentage: </span>${discountedValuePercentage.toFixed(2)}%`
                );
            });

            $(document).on('click', '.clear_discount_btn', function() {
                var discountedSellingPrice = 0;
                var discountedValue = parseFloat($(this).closest('tr').find('input[name="discounted_amount[]"]').val());
                var price = parseFloat($(this).closest('tr').find('input[name="price[]"]').val());
                var netDiscount = price - discountedSellingPrice;
                var discountedValuePercentage = (netDiscount / price) * 100;

                if(discountedSellingPrice > price) {
                    toastr.error("Discounted selling price cannot be greater than price", "Validation Error");
                    return;
                }

                $(this).closest('tr').find('input[name="discounted_amount[]"]').val(netDiscount.toFixed(2));
                $(this).closest('tr').find('input[name="discounted_selling_price[]"]').val(discountedSellingPrice.toFixed(2));
                $(this).closest('tr').find('input[name="discount_percentage_value[]"]').val(discountedValuePercentage.toFixed(2));

                $(this).closest('tr').find('.discount_info').html(`<span class="fw-bold">Discount Amount: </span>`+(netDiscount.toFixed(2) ? netDiscount.toFixed(2) : '')+`<br/><span class="fw-bold"> Discount Percentage: </span>`+(discountedValuePercentage.toFixed(2) ? discountedValuePercentage.toFixed(2) + `%` : ''));
            });

            $(document).on('click', '#apply', function() {
                var input_discount_percentage = $('#input_discount_percentage').val();
                if (input_discount_percentage === '') {
                    toastr.error("Please select a discount percentage", "Validation Error");
                    return;
                }
                var discount = parseFloat(input_discount_percentage);

                if (isNaN(discount) || discount < 0 || discount > 100) {
                    toastr.error("Please enter a valid discount percentage between 0 and 100", "Validation Error");
                    return;
                }

                if($('#is_view_product').val() == 0) {
                    toastr.error("Please get products first", "Validation Error");
                    return;
                }

                $('#product_list_body tr').each(function() {
                    var price = parseFloat($(this).find('td:nth-child(4)').text());
                    var discountedSellingPrice = price - (price * (discount / 100));
                    var discountedValue = price * (discount / 100);
                    $(this).find('input[name="discounted_selling_price[]"]').val(discountedSellingPrice.toFixed(2));
                    $(this).find('input[name="discounted_amount[]"]').val(discountedValue.toFixed(2));
                    $(this).find('input[name="discount_percentage_value[]"]').val(discount.toFixed(2));
                    $(this).find('.discount_info').html(`<span class="fw-bold">Discount Amount: </span>`+(discountedValue.toFixed(2) ? discountedValue.toFixed(2) : '')+`<br/><span class="fw-bold"> Discount Percentage: </span>`+(discount.toFixed(2) ? discount.toFixed(2) + `%` : ''));
                });
            });

            $(document).on('click', '.final_save_btn', function() {
                save();
            });
        });
    </script>
@endpush
