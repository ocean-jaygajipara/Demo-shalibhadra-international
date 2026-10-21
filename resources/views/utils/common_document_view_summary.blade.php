{{--
    Common Document Summary Component
    Used by common_document_view.blade.php to display order summary
--}}

@if(isset($isPrintMode) && $isPrintMode)
<div class="table-responsive">
    <table class="table table-hover table-bordered m-n invoice border-panel"
        style="color: #000;border-top: 3px solid {{ $borderColor }} !important;">
@else
<div class="card mb-3">
    <div class="card-body border-panel"
        style="border-top: 3px solid {{ $borderColor }} !important;">
        <table class="table table-hover">
@endif
    <div class="card-body border-panel"
        style="border-top: 3px solid {{ $borderColor }} !important;">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th colspan="2" class="text-center">Order Summary (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Sub Total</td>
                    <td class="text-end"><b>₹{{ number_format($sub_total, 2) }}</b></td>
                </tr>
                @if (!empty($document->transport_charge) && $document->transport_charge > 0)
                    <tr>
                        <td>Transport</td>
                        <td class="text-end"><b>₹{{ $document->transport_charge }}</b></td>
                    </tr>
                @endif

                @if (!empty($document->packing_forwarding_charge) && $document->packing_forwarding_charge > 0)
                    <tr>
                        <td>Packing & Forwarding</td>
                        <td class="text-end"><b>₹{{ $document->packing_forwarding_charge }}</b></td>
                    </tr>
                @endif

                @if (!empty($document->freight_charge) && $document->freight_charge > 0)
                    <tr>
                        <td>Freight Charge</td>
                        <td class="text-end"><b>₹{{ $document->freight_charge }}</b></td>
                    </tr>
                @endif

                @if (!empty($document->total_taxable) && $document->total_taxable > 0)
                <tr>
                    <td>Total Taxable</td>
                    <td class="text-end"><b>₹{{ $document->total_taxable }}</b></td>
                </tr>
                @endif

                @php
                    $customerStateName = $document->customer?->state?->name ?? null;
                @endphp

                @if (strtolower($customerStateName) === 'gujarat')
                    <tr>
                        <td>CGST</td>
                        <td class="text-end"><b>₹{{ number_format($document->gst_amount / 2, 2) }}</b></td>
                    </tr>
                    <tr>
                        <td>SGST</td>
                        <td class="text-end"><b>₹{{ number_format($document->gst_amount / 2, 2) }}</b></td>
                    </tr>
                @else
                    <tr>
                        <td>IGST</td>
                        <td class="text-end"><b>₹{{ number_format($document->gst_amount, 2) }}</b></td>
                    </tr>
                @endif

                @if (!empty($document->tcs_amount) && $document->tcs_amount > 0)
                    <tr>
                        <td>TCS Amount</td>
                        <td class="text-end"><b>₹{{ $document->tcs_amount }}</b></td>
                    </tr>
                @endif

                @if (!empty($document->round_off) && $document->round_off > 0)
                <tr>
                    <td>Rounded Off</td>
                    <td class="text-end"><b>₹{{ $document->round_off }}</b></td>
                </tr>
                @endif

                @if (!empty($document->grand_total) && $document->grand_total > 0)
                <tr>
                    <td><b>Grand Total</b></td>
                    <td class="text-end"><b>₹{{ $document->grand_total }}</b></td>
                </tr>
                @endif
            </tbody>
        </table>
@if(isset($isPrintMode) && $isPrintMode)
    </div>
@else
    </div>
</div>
@endif

