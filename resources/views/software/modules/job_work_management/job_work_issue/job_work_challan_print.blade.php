<div style="width: 100%; padding: 0;">

        <table style="width: 100%; border:1px solid #000;">
            <tr style="height:80px;">
                <td
                    style="width: 20%; text-align:center; border-right:1px solid #000; vertical-align: middle; border-left: none; border-top: none; border-bottom: none;">
                    <img src="{{ $jobIssue->company->order_header_logo_url}}"
                        style="height:40px;">
                </td>
                <td
                    style="width: 60%; text-align:center; font-size:30px; font-weight:bold; border-right:1px solid #000; vertical-align: middle; border-top: none; border-bottom: none;">
                    Delivery Challan
                </td>
                <td
                    style="width: 20%; text-align:center; font-weight:bold; vertical-align: middle;font-size: 20px; border-right: none; border-top: none; border-bottom: none;">
                    {{ $copyType }}
                </td>
            </tr>
        </table>

        <table class="movement-rule" style="border-left: 1px solid #000; border-right: 1px solid #000;">
            <tr style="height:52px;">
                <td
                    style="font-size:15px; line-height:1.4; vertical-align: middle; padding: 5px; border-left: none; border-right: none;">
                    Movement of inputs or partially processed goods for job-work under Rule 55 of The Central Goods And
                    Service Tax Rules, 2017 from one factory to another factory for further processing / operation.
                </td>
            </tr>
        </table>

        <table
            style="width:100%; border-top: 1px solid #000; border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; table-layout: fixed;">
            <tr>
                <td
                    style="width:50%; vertical-align: top; font-size:14px; padding:0; border-right: 1px solid #000; border-left: none; border-top: none; border-bottom: none;">
                    <div style="display:flex; flex-direction:column; box-sizing:border-box; min-height: 100%;">

                        <div style="padding:8px; border-bottom:1px solid #000; min-height: 120px; max-height: 140px;">
                            <div style="font-weight:bold; margin-bottom:5px;">Name and Address of Supplier /
                                Manufacturer:
                            </div>
                            <div style="margin-bottom:5px;">
                                {{ $jobIssue->company->company_name ?? '-' }}<br>
                                    {!! nl2br(strip_tags($jobIssue->company->address)) !!}

                            </div>
                            <div><b>GST NO:</b> {{ $jobIssue->company->gst_no ?? '-' }}</div>
                        </div>

                        <div style="padding:8px; font-size:14px; line-height:20px;min-height: 140px;">
                            <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:16px;">
                                <div>Description</div>
                                <div style="width:120px; text-align:right;">Qty (Kgs)</div>
                            </div>

                            <div class="full-width-line" style="margin-top: 12px;"></div>

                            @foreach ($jobIssue->job_work_issue_items as $item)
                              @php
                                    $gross_weight = floatval($item->gross_weight ?? 0);
                                    $bl_percentage = floatval($item->bl_percentage ?? 0);
                                    // dd($issue_weight,$bl_percentage);
                                    // BL weight calculation
                                    $bl_weight = ($gross_weight * $bl_percentage) / 100;

                                    // Final weight
                                    $final_weight = $gross_weight + $bl_weight;
                                    // dd($final_weight);
                                @endphp
                                <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                                    <div>{{ $item->product_name }}</div>
                                    <div style="width:120px; text-align:right;">
                                        {{ formatWeight($final_weight) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div style="padding:8px; font-size:14px;">
                            <div style="margin-top:10px;">Vehicle No : <b>{{ !empty($jobIssue->vehicle_no) ? $jobIssue->vehicle_no : 'GJ 10 TW 4865' }}</b></div>

                            <div style="border-top:1px solid #000; margin:10px -8px;"></div>

                            <div style="display:flex; justify-content:space-between; font-weight:bold;">
                                <div>3. Quantity (Pcs / Kgs)</div>
                                <div>{{ number_format($jobIssue->total_gross_weight, 3) }}</div>
                            </div>
                        </div>

                        <div style="padding:8px; border-top:1px solid #000;">
                            <div style="display:flex; justify-content:space-between; font-weight:bold;">
                                <div>4. Tariff Classification</div>
                                <div>{{ $jobIssue->tariff_code ?? '-' }}</div>
                            </div>
                        </div>

                        <div style="padding:8px; border-top:1px solid #000;">
                            <div style="font-weight:bold; margin-bottom:5px;">5. Estimated Value / Tax</div>

                            <div style="border:1px solid #000;">
                                <div style="display:flex; font-weight:bold; border-bottom:1px solid #000;">
                                    <div style="flex:1; padding:5px; border-right:1px solid #000; text-align:center;">
                                        Total Amount
                                    </div>
                                    <div
                                        style="width:100px; text-align:center; padding:5px; border-right:1px solid #000;">
                                        CGST Rs.
                                    </div>
                                    <div
                                        style="width:100px; text-align:center; padding:5px; border-right:1px solid #000;">
                                        SGST Rs.
                                    </div>
                                    <div style="width:100px; text-align:center; padding:5px;">
                                        IGST Rs.
                                    </div>
                                </div>

                                <div style="display:flex;">
                                    <div style="flex:1; padding:5px; border-right:1px solid #000; text-align:left;">
                                        {{ number_format($jobIssue->sub_total, 1) }}
                                    </div>
                                    <div
                                        style="width:100px; text-align:center; padding:5px; border-right:1px solid #000;">
                                        {{ number_format($jobIssue->total_cgst_amount, 2) }}
                                    </div>
                                    <div
                                        style="width:100px; text-align:center; padding:5px; border-right:1px solid #000;">
                                        {{ number_format($jobIssue->total_sgst_amount, 2) }}
                                    </div>
                                    <div style="width:100px; text-align:center; padding:5px;">
                                        {{ number_format($jobIssue->igst_amount ?? 0, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="padding:8px; border-top:1px solid #000;">
                            <div style="font-weight:bold;">
                                6. Date & Time of Issue :
                                <span style="font-weight:normal;">
                                    {{ \Carbon\Carbon::parse($jobIssue->job_work_issue_date)->format('d/m/Y - h:i A') }}
                                </span>
                            </div>
                        </div>

                        <div style="padding:8px; border-top:1px solid #000;min-height: 70px;">
                            <div style="font-weight:bold;">
                                7. Nature of processing/Manufacturing required :
                                <br/> <br/>
                                <span style="font-weight:normal;margin-top:10px;">{{ $jobIssue->job_work_type->name ?? '-' }}</span>
                            </div>
                        </div>

                        <div style="padding:8px; border-top:1px solid #000;min-height: 120px;">
                            <div style="font-weight:bold;">8. Factory/Place of processing:<br>
                                <span style="font-weight:normal;">
                                    {{ $jobIssue->vendor->vendor_name ?? '-' }}<br>
                                    {{ $jobIssue->vendor->address ?? '-' }}<br>
                                    GSTIN: {{ $jobIssue->vendor->gst_number ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div style="padding:8px; border-top:1px solid #000; flex-grow: 1;">
                            <div style="margin-top:10px; font-weight:bold;">
                                For, <b>{{ $jobIssue->company->company_name ?? '-' }}</b><br><br>
                                Date : {{ \Carbon\Carbon::today()->format('d/m/Y') }}<br>
                                Place : {{ $jobIssue->city->city ?? '-' }}<br>
                                Authorised Signature
                            </div>
                        </div>
                    </div>
                </td>

                <td
                    style="width:50%; padding:0; vertical-align: top; font-size:14px; text-align:center; border-right: none; border-left: none; border-top: none; border-bottom: none;">
                    <div style="display:flex; flex-direction:column; box-sizing:border-box; min-height: 100%;">

                        <div style="border-bottom:1px solid #000; display:flex;">
                            <div style="width:50%; border-right:1px solid #000; box-sizing:border-box; padding:8px;">
                                <div style="font-size:12px; font-weight:bold; text-align:left;">
                                    To be filled by the processing unit in Original and Duplicate Challan
                                </div>
                            </div>

                            <div style="width:50%; box-sizing:border-box; padding:8px;">
                                <div style="font-size:16px; font-weight:bold;">
                                    Serial Number<br>
                                    {{ $jobIssue->job_work_issue_no ?? '-' }}
                                </div>
                            </div>
                        </div>

                        <div
                            style="padding:8px; box-sizing:border-box; border-bottom:1px solid #000; margin-top: 13px;">
                            <div style="font-weight:bold; font-size: 12px; text-align:left;">
                                1. Details of type, qty, date & time of processing done and return of processed goods to
                                parent factory:
                            </div>
                        </div>

                        <div style="display: flex; font-size:14px; border-bottom:1px solid #000;height: 39px;">
                            <div
                                style="flex:1; border-right:1px solid #000; font-size:16px; text-align:center; box-sizing:border-box; padding:5px 0;">
                                <b>Size/Type</b>
                            </div>

                            <div
                                style="flex:1; border-right:1px solid #000; font-size:16px; text-align:center; box-sizing:border-box; padding:5px 0;">
                                <b>Quantity</b>
                            </div>

                            <div
                                style="flex:1; text-align:center; font-size:16px; box-sizing:border-box; padding:5px 0;">
                                <b>Date</b>
                            </div>
                        </div>

                        <div style="display: flex; min-height: 337px; border-bottom: 1px solid #000;">
                            <div style="flex:1; border-right:1px solid #000;"></div>
                            <div style="flex:1; border-right:1px solid #000;"></div>
                            <div style="flex:1;"></div>
                        </div>

                        <div style="padding:8px; text-align: left; border-bottom: 1px solid #000;min-height: 55px !important; max-height:55px !important;">
                            <div style="font-weight:bold;">
                                2. Nature of Proc. / Manufacturing done
                            </div>
                        </div>

                        <div style="padding:8px; text-align: left; border-bottom: 1px solid #000;min-height: 70px; max-height: 70px;">
                            <div style="font-weight:bold;">
                                3. Qty of waste material / reject returned to the factory & Burning loss/Process loss
                            </div>
                        </div>

                        <div style="padding:8px; text-align: left; border-bottom: 1px solid #000;min-height: 120px;">
                            <div style="font-weight:bold;">
                                4. Name & Address of the Processor :<br>
                                <span style="font-weight:normal;">
                                    {{ $jobIssue->vendor->vendor_name ?? '-' }}<br>
                                    {{ $jobIssue->vendor->address ?? '-' }}<br>
                                    GSTIN: {{ $jobIssue->vendor->gst_number ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div style="padding:8px; text-align: left;">
                            <div style="font-weight:bold;">
                                5. Signature of Processor :
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
