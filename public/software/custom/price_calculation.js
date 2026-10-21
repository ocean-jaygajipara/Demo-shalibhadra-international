function updateInnerAndOuterFromQty(t) {
    recalculateRow(t);
}

$('#is_gst_applicable').click(function() {
    recalculateRow();
});

$('#is_tcs_applicable').click(function() {
    recalculateRow();
});

function checkMax(input) {
    if (input.value > 100) {
        input.value = '';
        toastr.error("Discount percentage cannot be more than 100!");
    }
}

function validateAndRecalculate(input, row) {
    validateFlatDiscount(input);
    recalculateRow(input, row);
}

function validateFlatDiscount(input) {
    var price = $(input).closest('tr').find('.price').val();
    var discount = parseFloat(input.value);

    if (discount > parseFloat(price)) {
        toastr.error("Discount (Flat) cannot be more than Price (in INR)!");
        input.value = '';
    }
}

 $(document).on('change', '#additional_discount_amount, #cash_discount_percentage', function(e) {
    var total_taxable = parseFloat($("#total_taxable").val());
    if (!total_taxable || parseInt(total_taxable) <= 0) {
        toastr.error("Please Enter valid Discount Amount!");
        $("#additional_discount_amount").val('0');
        recalculateRow(e.target);
        return;
    }
});

function recalculateRow(t, discount_type = "") {
    var cash_discount_amount = parseFloat($("#cash_discount_amount").val()) || 0;
    var master_amount = parseFloat($("#master_amount").val()) || 0;
    var additional_discount_amount = parseFloat($("#additional_discount_amount").val()) || 0;

    if(cash_discount_amount > master_amount) {
        toastr.error("Cash Discount Amount cannot be more than Master Amount!");
        $("#cash_discount_amount").val('0');
        return;
    }

    if(additional_discount_amount > master_amount) {
        toastr.error("Additional Discount Amount cannot be more than Master Amount!");
        $("#additional_discount_amount").val('0');
        return;
    }

    var row = $(t).parent('td').parent('tr');
    var total_row = $('#productBody tr').length;

    if (discount_type == 1) {
        $(row).find("td").find("input.discount_percentage").val("");
    } else if (discount_type == 2) {
        $(row).find("td").find("input.discount_flat").val("");
    }

    var price = $(row).find("td").find("input.price").val();
    var quantity = $(row).find("td").find("input.quantity").val();
    var grosse_per_qty = $(row).find("input.grosse_per_qty").val();
    var unit = $(row).find("td").find("input.unit").val();
    var discount_flat = $(row).find("td").find("input.discount_flat").val();
    var discount_percentage = $(row).find("td").find("input.discount_percentage").val();
    var taxable_amount = $(row).find("td").find("input.taxable_amount").val();

    var master_quantity = $("#master_quantity").val();
    var master_amount = $("#master_amount").val();
    var master_taxable_amount = $("#master_taxable_amount").val();
    var master_gst_amount = $("#master_gst_amount").val();
    var master_sub_total = $("#master_sub_total").val();

    var cash_discount_percentage = $("#cash_discount_percentage").val();
    var additional_discount_percentage = $("#additional_discount_percentage").val();

    var packing_forwarding_charge = $("#packing_forwarding_charge").val();
    var transport = $("#transport").val();
    var freight = $("#freight").val();


    // if (quantity) {
    //     const isValidDecimal = /^(\d+(\.\d{0,2})?)?$/.test(quantity);

    //     if (!isValidDecimal) {
    //         if (qty_allow_option == 0) {
    //             toastr.error("Only numbers and one decimal point allowed!");
    //             quantity = quantity.replace(/[^0-9.]/g, "");

    //             // Ensure only one dot remains
    //             const parts = quantity.split(".");
    //             if (parts.length > 2) {
    //                 quantity = parts[0] + "." + parts[1];
    //             }
    //         }
    //     }
    // }

    if(discount_flat < 0) {
        toastr.error("Nagative Value Not Allowed!");
        $(row).find("td").find("input.discount_flat").val("");
        discount_flat = 0;
    }

    if(discount_percentage < 0) {
        toastr.error("Nagative Value Not Allowed!");
        $(row).find("td").find("input.discount_percentage").val("");
        discount_percentage = 0;
    }

    if (isNaN(packing_forwarding_charge) || packing_forwarding_charge == "NaN" || packing_forwarding_charge == "") {
        packing_forwarding_charge = 0;
    } else if (parseFloat(packing_forwarding_charge) < 0) {
        toastr.error("Nagative Value Not Allowed!");
        $("#packing_forwarding_charge").val("");
        packing_forwarding_charge = 0;
    }

    if (isNaN(transport) || transport == "NaN" || transport == "") {
        transport = 0;
    } else if (parseFloat(transport) < 0) {
        toastr.error("Nagative Value Not Allowed!");
        $("#transport").val("");
        transport = 0;
    }
    if (isNaN(freight) || freight == "NaN" || freight == "") {
        freight = 0;
    } else if (parseFloat(freight) < 0) {
        toastr.error("Nagative Value Not Allowed!");
        $("#freight").val("");
        freight = 0;
    }

    if (isNaN(master_quantity) || master_quantity == "NaN" || master_quantity == "") {
        master_quantity = 0;
    }

    if (isNaN(master_amount) || master_amount == "NaN" || master_amount == "") {
        master_amount = 0;
    }

    if (isNaN(master_taxable_amount) || master_taxable_amount == "NaN" || master_taxable_amount == "") {
        master_taxable_amount = 0;
    }

    if (isNaN(master_gst_amount) || master_gst_amount == "NaN" || master_gst_amount == "") {
        master_gst_amount = 0;
    }

    if (isNaN(master_sub_total) || master_sub_total == "NaN" || master_sub_total == "") {
        master_sub_total = 0;
    }

    if (isNaN(quantity) || quantity == "NaN" || quantity == "") {
        quantity = 0;
    }

    if (isNaN(discount_flat) || discount_flat == "NaN" || discount_flat == "") {
        discount_flat = 0;
    }

    if (isNaN(discount_percentage) || discount_percentage == "NaN" || discount_percentage == "") {
        discount_percentage = 0;
    }

    if (isNaN(cash_discount_percentage) || cash_discount_percentage == "NaN" || cash_discount_percentage == "") {
        cash_discount_percentage = 0;
    }

    if (isNaN(additional_discount_percentage) || additional_discount_percentage == "NaN" || additional_discount_percentage == "") {
        additional_discount_percentage = 0;
    }

    if (isNaN(taxable_amount) || taxable_amount == "NaN" || taxable_amount == "") {
        taxable_amount = 0;
    }

    var per_product_packing_charge = parseFloat(packing_forwarding_charge / total_row);
    var per_transport_charge = parseFloat(transport / total_row);
    var per_freight_charge = parseFloat(freight / total_row);


    var amount = 0;
    if (discount_flat != "" && discount_flat != 0) {
        var rate_in_amount = (parseFloat(price) - parseFloat(discount_flat));
    } else {
        var rate_in_amount = (parseFloat(price) * (1 - (parseFloat(discount_percentage) / 100)));
    }
    if(unit == 'gross'){
        // amount = parseFloat(rate_in_amount) * parseFloat(quantity) * parseFloat(grosse_per_qty);
        amount = parseFloat(rate_in_amount) * parseFloat(quantity);
    }else{
        amount = parseFloat(rate_in_amount) * parseFloat(quantity);
    }

    $(row).find("td").find("input.rate_in_amount").val(rate_in_amount.toFixed(2));
    $(row).find("td").find("input.amount").val(amount.toFixed(2));
    $(row).find("td").find("input.taxable_amount").val(amount.toFixed(2));
    // Update taxable display
    $(row).find("td").find(".taxable-display, small.text-muted").text("Taxable: " + amount.toFixed(2));

    let totalQty = 0;
    $('input[name="quantity[]"]').each(function (index) {
        let quantity = parseFloat($(this).val()) || 0;
        let unit = $('input[name="unit[]"]').eq(index).val();
        let grosse_per_qty = parseFloat($('input[name="grosse_per_qty[]"]').eq(index).val()) || 1;

        // Convert gross to pieces for total quantity calculation
        // if (unit && unit.toLowerCase() === 'gross') {
        //     totalQty += quantity * grosse_per_qty;
        // } else {
            totalQty += quantity;
        //}
    });

    let totalamount = 0;
    let total_taxable_amount = 0;
    let total_cash_discount_amount = 0;
    let total_additional_discount_amount = 0;
    let is_igst = $("#is_igst").val();
    $('input[name="amount[]"]').each(function (index) {
        let amount = parseFloat($(this).val()) || 0;
        let gst_in_percentage = $('input[name="gst_in_percentage[]"]').eq(index).val();
        let cgst_in_percentage = $('input[name="cgst_in_percentage[]"]').eq(index).val();
        let sgst_in_percentage = $('input[name="sgst_in_percentage[]"]').eq(index).val();

        var input_cash_discount_amount = $("#cash_discount_amount").val() || 0;
        var input_cash_discount_amount_per_product = parseFloat(input_cash_discount_amount) / total_row;

        var input_additional_discount_amount = $("#additional_discount_amount").val() || 0;
        var input_additional_discount_amount_per_product = parseFloat(input_additional_discount_amount) / total_row;

        totalamount += amount;
        let cash_discount_amount = (cash_discount_percentage !== 0) ? (amount * (cash_discount_percentage / 100)) : input_cash_discount_amount_per_product;
        let discounted_amount = amount - cash_discount_amount;
        total_cash_discount_amount += parseFloat(cash_discount_amount);

        let additional_discount_amount = (additional_discount_percentage !== 0) ? (discounted_amount * (additional_discount_percentage / 100)) : input_additional_discount_amount_per_product;
        let new_taxable_amount = discounted_amount - additional_discount_amount;
        total_additional_discount_amount += parseFloat(additional_discount_amount);

        new_taxable_amount += per_product_packing_charge + per_transport_charge +per_freight_charge;
        $('input[name="taxable_amount[]"]').eq(index).val(new_taxable_amount.toFixed(2));
        total_taxable_amount += new_taxable_amount;

        // Update taxable display
        $('input[name="amount[]"]').eq(index).closest('td').find('.taxable-display, small.text-muted').text("Taxable: " + new_taxable_amount.toFixed(2));


        let gst_amount = 0, cgst_amount = 0, sgst_amount = 0, igst_amount = 0;
        if (is_igst == 1) {
            let gst_percent = parseFloat(gst_in_percentage) || 0;
            igst_amount = new_taxable_amount * (gst_percent / 100);
            gst_amount = igst_amount;

        } else {
            // Check if CGST and SGST percentages are set directly (e.g., for merchant exporter)
            let cgst_percent = parseFloat(cgst_in_percentage) || 0;
            let sgst_percent = parseFloat(sgst_in_percentage) || 0;

            if (cgst_percent > 0 || sgst_percent > 0) {
                // Use direct CGST and SGST percentages
                cgst_amount = new_taxable_amount * (cgst_percent / 100);
                sgst_amount = new_taxable_amount * (sgst_percent / 100);
            } else if (gst_in_percentage && gst_in_percentage !== '') {
                // Fallback to dividing GST percentage by 2
                let gst_percent = parseFloat(gst_in_percentage) || 0;
                cgst_amount = new_taxable_amount * ((gst_percent / 2) / 100);
                sgst_amount = new_taxable_amount * ((gst_percent / 2) / 100);
            } else {
                // No GST percentages set, set amounts to 0
                cgst_amount = 0;
                sgst_amount = 0;
            }
            gst_amount = cgst_amount + sgst_amount;
        }

        var sub_total = (parseFloat(new_taxable_amount) || 0) + (parseFloat(gst_amount) || 0);
        if (is_igst == 1) {
            $('input[name="gst_amount_array[]"]').eq(index).val(igst_amount.toFixed(2));
        }else{

            $('input[name="cgst_amount_array[]"]').eq(index).val(cgst_amount.toFixed(2));
            $('input[name="sgst_amount_array[]"]').eq(index).val(sgst_amount.toFixed(2));
        }
        $('input[name="subtotal[]"]').eq(index).val(sub_total.toFixed(2));
    });


    let total_gst_amount = 0;
    if (is_igst == 1) {
        $('input[name="gst_amount_array[]"]').each(function () {
            total_gst_amount += parseFloat($(this).val()) || 0;
        });
    } else {
        $('input[name="cgst_amount_array[]"]').each(function () {
            total_gst_amount += parseFloat($(this).val()) || 0;
        });

        $('input[name="sgst_amount_array[]"]').each(function () {
            total_gst_amount += parseFloat($(this).val()) || 0;
        });
    }


    $("#master_quantity").val(totalQty.toFixed(2));
    $("#master_amount").val(totalamount.toFixed(2));
    $("#master_taxable_amount").val(total_taxable_amount.toFixed(2));
    $('#total_taxable').val(total_taxable_amount.toFixed(2));
    $("#master_gst_amount").val(total_gst_amount.toFixed(2));
    let subtotal = parseFloat(total_gst_amount) + parseFloat(total_taxable_amount);
    $("#master_sub_total").val(subtotal.toFixed(2));
    //$("#master_sub_total").val(parseFloat(total_gst_amount.toFixed(2)) + parseFloat(total_taxable_amount.toFixed(2)));

    total_cash_discount_amount = parseFloat(total_cash_discount_amount) || 0;
    $("#cash_discount_amount").val(total_cash_discount_amount.toFixed(2));

    total_additional_discount_amount = parseFloat(total_additional_discount_amount) || 0;
    $("#additional_discount_amount").val(total_additional_discount_amount.toFixed(2));

    if ($("#is_gst_applicable").is(":checked")) {
        $('#gst_amount').val(total_gst_amount.toFixed(2));
    } else {
        $('#gst_amount').val(0.00);
    }

    if ($("#is_tcs_applicable").is(":checked")) {
        var temp_total = parseFloat($('#gst_amount').val()) + total_taxable_amount;
        var tcs_amount = temp_total * (0.1 / 100);
        $('#tcs_amount').val(tcs_amount.toFixed(2));
    } else {
        $('#tcs_amount').val(0.00);
    }

    var grand_total = (parseFloat($('#gst_amount').val()) || 0) + (parseFloat(total_taxable_amount) || 0) + (parseFloat($('#tcs_amount').val()) || 0);
    let decimalPart = grand_total % 1;
    $('#round_off').val(decimalPart.toFixed(2));
    $('#grand_total').val(Math.round(grand_total.toFixed(2)));
}
