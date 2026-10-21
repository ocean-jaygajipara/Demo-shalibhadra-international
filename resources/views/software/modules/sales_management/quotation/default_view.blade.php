<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Quotation - Print</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <!-- Optional: Boxicons or other icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
   <style>
    @media print {
        body {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
            font-size: 13px !important;
        }

        .row.print-flex,.row.print-flex2 {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
        }
        .row.print-flex > .col-md-3 {
            width: 30% !important;
            max-width: 30% !important;
            flex: 0 0 30% !important;
        }
        .row.print-flex > .col-md-4 {
            width: 45% !important;
            max-width: 45% !important;
            flex: 0 0 45% !important;
        }
        .row.print-flex > .col-md-6 {
            width: 50% !important;
            max-width: 50% !important;
            flex: 0 0 50% !important;
        }
        .row.print-flex > .col-md-7 {
            width: 55% !important;
            max-width: 55% !important;
            flex: 0 0 55% !important;
        }
        .row.print-flex > .col-md-5 {
            width: 45% !important;
            max-width: 45% !important;
            flex: 0 0 45% !important;
        }
        .container-fluid, .container-fuild {
            width: 100% !important;
        }
        .address_detail{
             display: flex !important;
        }
        /* .discount-summary {
            margin-top: 500px !important;
        }
        .discount-summary > .col-md-7, .discount-summary > .col-md-5 {
            margin-top: 50px !important;
        } */

         /* Make the table fit the page */
        .table-responsive {
            overflow: visible !important;
        }
        .table {
            table-layout: fixed;
            width: 100% !important;
            word-wrap: break-word;
            font-size: 13px; /* Reduce font size to fit more columns */
        }
        .table th,
        .table td {
            padding: 4px !important;
            word-break: break-word;
        }
        /* Optional: Reduce margins to use more page space */
        @page {
            margin: 10mm;
        }
    }
    </style>
</head>

<body style="--bs-scrollbar-width: 0px;" onload="printAndRedirect();">

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-fluid flex-grow-1 px-0 py-0">
                    <div class="border container-fluid px-0 py-0">

                        <!-- Customer and Quotation Info -->
                        <div class="row border g-0 print-flex">
                            <!-- Customer Details -->
                            <div class="col-md-6" style="border-right:2px solid #dee2e6;">
                                <div class="p-3 bg-white h-100">
                                    <h5 class="mb-3">Customer Details</h5>
                                    <div class="row mb-2">
                                        <label class="col-6 fw-bold">Customer Name:</label>
                                        <div class="col-6">{{ $quotation->customer->customer_name ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <label class="col-6 fw-bold">Person Name:</label>
                                        <div class="col-6">{{ $quotation->company->person_name ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <label class="col-6 fw-bold">Phone:</label>
                                        <div class="col-6">{{ $quotation->phone ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <label class="col-6 fw-bold">Address:</label>
                                        <div class="col-6">{{ $quotation->address ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <label class="col-6 fw-bold">GSTIN:</label>
                                        <div class="col-6">{{ $quotation->gst_no ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quotation Info -->
                            <div class="col-md-6">
                                <div class="p-3 bg-white h-100">
                                    <h5 class="mb-3">Quotation Info</h5>
                                    <div class="row mb-2">
                                        <label class="col-6 fw-bold">Quotation No:</label>
                                        <div class="col-6">{{ $quotation->quotation_no ?? '-' }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <label class="col-6 fw-bold">Quotation Date:</label>
                                        <div class="col-6">{{ $quotation->quotation_date ?? '-' }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <label class="col-6 fw-bold">Transport By:</label>
                                        <div class="col-6">{{ $quotation->transport_by->name ?? '-' }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <label class="col-6 fw-bold">Transporter Detail:</label>
                                        <div class="col-6">{{ $quotation->transport_detail->name ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Details -->

                        {{-- <div class="border border-start border-end p-3 bg-white">

                            <div class="d-flex justify-content-between flex-wrap">
                                <h5 class="">Address Details</h5>
                                <div class="me-4"><strong>Shipping Address:</strong> {{ $quotation->shipping_address }}</div>
                                <div><strong>Billing Address:</strong> {{ $quotation->billing_address }}</div>
                            </div>
                        </div> --}}


                        <!-- Quotation Items -->

                        <div class="border table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border px-3 py-2">Item</th>
                                        <th class="border px-3 py-2">HSN</th>
                                        <th class="border px-3 py-2">Unit</th>
                                        <th class="border px-3 py-2">Qty</th>
                                        <th class="border px-3 py-2">Price</th>
                                        <th class="border px-3 py-2">Discount %</th>
                                        <th class="border px-3 py-2">Rate</th>
                                        <th class="border px-3 py-2">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $sub_total = 0; @endphp
                                    @forelse($quotation->quotation_items as $item)
                                        @php $sub_total += $item->sub_total; @endphp
                                        <tr>
                                            <td class="border px-3 py-2">{{ $item->product_name }}</td>
                                            <td class="border px-3 py-2">{{ $item->hsn_code }}</td>
                                            <td class="border px-3 py-2">{{ $item->unit }}</td>
                                            <td class="border px-3 py-2">{{ $item->quantity }}</td>
                                            <td class="border px-3 py-2">{{ $item->price_in_amount }}</td>
                                            <td class="border px-3 py-2">{{ $item->discount_percentage }}</td>
                                            <td class="border px-3 py-2">{{ $item->rate_in_amount }}</td>
                                            <td class="border px-3 py-2">{{ $item->amount }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No items found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>


                        <!-- Bank Details and Total Summary -->
                        <div class="row g-0 print-flex" >
                            <!-- Bank Details -->
                           <div class="border col-md-7">
                                <div class="p-3 bg-white h-100">

                                    <div class="mb-2 d-flex">
                                        <label class="fw-bold me-2" style="min-width: 130px;">Bank Details:</label>
                                        <div>{!! str_replace('</p>', '</p>' . PHP_EOL, $quotation->company->bank_details ?? '-') !!}</div>
                                    </div>

                                    <!-- Terms and Conditions -->
                                    <div class="mb-2 d-flex">
                                        <label class="fw-bold me-2" style="min-width: 130px;">Terms and Conditions:</label>
                                        <div>{{ str_replace("&nbsp;", "", strip_tags($quotation->term_and_condition)) ?? '-' }}</div>
                                    </div>

                                    <!-- Regards -->
                                    <div class="mb-2 d-flex">
                                        <label class="fw-bold me-2" style="min-width: 130px;">Regards:</label>
                                        <div>{{ str_replace("&nbsp;", "", strip_tags($quotation->regards)) ?? '-' }}</div>
                                    </div>

                                    <!-- Note -->
                                    <div class="mb-2 d-flex">
                                        <label class="fw-bold me-2" style="min-width: 130px;">Note:</label>
                                        <div>{{ str_replace("&nbsp;", "", strip_tags($quotation->notes)) ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>


                            <!-- Total Summary -->
                            <div class="p-3 col-md-5">
                                <div class="bg-white h-100">
                                    <div class="mb-2 border-bottom pb-1"><strong>Sub Total:</strong> {{ $sub_total }}</div>
                                    <div class="mb-2 border-bottom pb-1"><strong>Cash Discount Amount:</strong> {{ $quotation->cash_discount_amount }}</div>
                                    <div class="mb-2 border-bottom pb-1"><strong>Additional Discount Amount:</strong> {{ $quotation->additional_discount_amount }}</div>
                                    <div class="mb-2 border-bottom pb-1"><strong>Transport:</strong> {{ $quotation->transport_charge }}</div>
                                    <div class="mb-2 border-bottom pb-1"><strong>Packing & Forwarding:</strong> {{ $quotation->packing_forwarding_charge }}</div>
                                    <div class="mb-2 border-bottom pb-1"><strong>Freight:</strong> {{ $quotation->freight_charge }}</div>
                                    <div class="mb-2 border-bottom pb-1"><strong>Total Taxable:</strong> {{ $quotation->total_taxable }}</div>
                                    <div class="mb-2 border-bottom pb-1"><strong>GST Amount:</strong> {{ $quotation->gst_amount }}</div>
                                    <div class="mb-2 border-bottom pb-1"><strong>TCS Amount:</strong> {{ $quotation->tcs_amount }}</div>
                                    <div class="mb-2 border-bottom pb-1"><strong>Rounded Off:</strong> {{ $quotation->round_off }}</div>
                                    <div class="mb-2 border-bottom pb-1"><strong>Grand Total:</strong> {{ $quotation->grand_total }}</div>
                                </div>
                            </div>

                        </div>

                    </div> <!-- container-fluid -->
                </div> <!-- content-wrapper -->
            </div> <!-- layout-page -->
        </div> <!-- layout-container -->
    </div> <!-- layout-wrapper -->
</div>


    <script>
        function printAndRedirect() {
            window.print();

            window.onafterprint = function() {
                setTimeout(function() {
                    window.location.href = "{{ route($modules['route'] . '.index') }}";
                }, 1);
            };
        }
    </script>
</body>

</html>

