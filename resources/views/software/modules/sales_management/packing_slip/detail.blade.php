@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;

    $authDetail = $authLoginUserDetail ?? (Auth::guard('admin_software')->user() ?? Auth::guard('team_person')->user());
    $isDhruvik = false;
    if ($authDetail) {
        $userCompanyId = (int)($authDetail->company_id ?? ($company_id ?? 0));
        $userTeamRoleId = (int)($authDetail->team_role_id ?? 0);
        $userParentTypeId = (int)($authDetail->parent_type_id ?? 0);
        $authName = strtoupper($authDetail->name ?? '');
        $authUsername = strtoupper($authDetail->username ?? '');
        $authEmpCode = strtoupper($authDetail->employee_code ?? '');

        if (
            (Auth::guard('admin_software')->check() && !Auth::guard('team_person')->check()) ||
            ($userCompanyId == 2 && ($userTeamRoleId == 2 || (int)$authDetail->id == 2 || $authEmpCode === 'EMP-001')) ||
            str_contains($authName, 'DHRUVIK') ||
            str_contains($authUsername, 'DHRUVIK')
        ) {
            $isDhruvik = true;
        }
    }
@endphp

@section('title', $page_title)

@section('content')
    <div class="px-1">
        <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
            @include('software.inlcudes.breadcrumb', [
                'breadcrumbArray' => [
                    ['title' => $page_title, 'url' => route($route . '.index')],
                    [
                        'title' => 'Packing Slip Detail',
                        'url' => '',
                    ],
                ],
                'show_filter_btn' => false,
                'show_back_btn' => true,
            ])
            <div class="d-flex gap-2">

                {{-- @if ($packingSlip->invoice)
                    <a href="{{ route('invoice.invoiceShow', [$packingSlip->invoice->id]) }}"
                        class="btn btn-primary waves-effect waves-light text-white mx-1" target="_blank">
                        <i class="ti ti-eye"></i> Invoice Generated
                    </a>
                @endif --}}
                {{-- <a href="{{ route($route . '.PackingSlipShow', [$packingSlip->id]) }}"
                   class="btn btn-info waves-effect waves-light text-white" target="_blank">
                    <i class="ti ti-printer"></i> Print
                </a> --}}
            </div>
        </div>
    </div>

    <div class="card my-3 mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Packing Slip No:</b>
                    <span>{{ $packingSlip->packing_slip_no ?? '--' }}</span>
                </div>
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Packing Slip Date:</b>
                    <span>{{ $packingSlip->packing_slip_date ? \Carbon\Carbon::parse($packingSlip->packing_slip_date)->format('d-m-Y') : '--' }}</span>
                </div>
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Status:</b>
                    @if ($packingSlip->sales_status)
                        <span class="badge bg-glow" style="background-color: {{ $packingSlip->sales_status->status_color }}">
                            {{ $packingSlip->sales_status->statusname }}
                        </span>
                    @else
                        <span>--</span>
                    @endif
                </div>
                @if (
                    ($modules['currentGuard'] === 'admin_software' ||
                        ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))) &&
                        $packingSlip->invoice)
                    <div class="col-md-3 col-sm-12 mb-2">
                        <b>Invoice No:</b>
                        <span class="text-primary">
                            <a href="{{ route('invoice.invoiceShow', [$packingSlip->invoice->id]) }}" target="_blank">
                                {{ $packingSlip->invoice->invoice_order_no ?? '--' }}
                            </a>
                        </span>
                    </div>
                @endif

                @if (!$company_id)
                    <div class="col-md-3 col-sm-12 mb-2">
                        <b>Company:</b>
                        <span>{{ $packingSlip->company->company_name ?? '--' }}</span>
                    </div>
                @endif

                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Sales Order No:</b>
                    <span>{{ $packingSlip->sales_order_no ?? '--' }}</span>
                </div>
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Sales Order Date:</b>
                    <span>{{ $packingSlip->sales_order_date ? \Carbon\Carbon::parse($packingSlip->sales_order_date)->format('d-m-Y') : '--' }}</span>
                </div>
                @if ($packingSlip->warehouse)
                    <div class="col-md-3 col-sm-12 mb-2">
                        <b>Warehouse:</b>
                        <span>{{ $packingSlip->warehouse->warehouse_name ?? '--' }}</span>
                    </div>
                @endif

                @if (
                    $modules['currentGuard'] === 'admin_software' ||
                        ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
                    <br />
                    <div class="divider">
                        <div class="divider-text">
                            <h3>Customer Details</h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-12 mb-2">
                        <b>Customer Name:</b>
                        <span>{{ $packingSlip?->customer_name ?? ($packingSlip?->customer?->customer_name ?? '--') }}</span>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <b>Phone:</b>
                        <span>{{ $packingSlip->phone ?? '--' }}</span>
                    </div>
                    <div class="col-md-3 col-sm-12 mb-2">
                        <b>State:</b>
                        <span>{{ $packingSlip->state ?? '--' }}</span>
                    </div>
                    @if ($packingSlip->address)
                        <div class="col-md-12 col-sm-12 mb-2">
                            <b>Address:</b>
                            <span>{{ $packingSlip->address }}</span>
                        </div>
                    @endif
                @endif

                <br />
                <div class="divider">
                    <div class="divider-text">
                        <h3>Packing Slip Items</h3>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Product Code</th>
                                    <th>Product Name</th>
                                    @php
                                        $hasHsn =
                                            $packingSlip->packing_slip_item &&
                                            $packingSlip->packing_slip_item
                                                ->whereNotNull('hsn_code')
                                                ->where('hsn_code', '!=', '')
                                                ->count() > 0;
                                        $hasCarton =
                                            $packingSlip->packing_slip_item &&
                                            $packingSlip->packing_slip_item->whereNotNull('no_of_carton')->count() > 0;
                                        $hasQtyPc =
                                            $packingSlip->packing_slip_item &&
                                            $packingSlip->packing_slip_item->whereNotNull('qty_pc')->count() > 0;
                                        $hasTotalPacket =
                                            $packingSlip->packing_slip_item &&
                                            $packingSlip->packing_slip_item->whereNotNull('total_packet')->count() > 0;
                                        $hasQtyCtn =
                                            $packingSlip->packing_slip_item &&
                                            $packingSlip->packing_slip_item->whereNotNull('qty_ctn')->count() > 0;
                                        $hasActualWtCtn =
                                            $packingSlip->packing_slip_item &&
                                            $packingSlip->packing_slip_item->whereNotNull('actual_wt_ctn')->count() > 0;
                                        $hasBoxNo =
                                            $packingSlip->packing_slip_item &&
                                            $packingSlip->packing_slip_item
                                                ->whereNotNull('box_no')
                                                ->where('box_no', '!=', '')
                                                ->count() > 0;
                                        $hasTotalQty =
                                            $packingSlip->packing_slip_item &&
                                            $packingSlip->packing_slip_item->whereNotNull('total_qty')->count() > 0;
                                        $hasTotalWeight =
                                            $packingSlip->packing_slip_item &&
                                            $packingSlip->packing_slip_item->whereNotNull('total_weight')->count() > 0;
                                    @endphp
                                    @if ($hasHsn)
                                        <th>HSN Code</th>
                                    @endif
                                    <th>Unit</th>
                                    <th>WT/PC</th>
                                    @if ($hasCarton)
                                        <th>No. of Carton</th>
                                    @endif
                                    @if ($hasQtyPc)
                                        <th>Qty Per PC</th>
                                    @endif
                                    @if ($hasTotalPacket)
                                        <th>Total Packet</th>
                                    @endif
                                    @if ($hasQtyCtn)
                                        <th>Qty CTN</th>
                                    @endif
                                    @if ($hasActualWtCtn)
                                        <th>Actual WT CTN</th>
                                    @endif

                                    @if ($hasBoxNo)
                                        <th>Box No</th>
                                    @endif
                                    @if ($hasTotalQty)
                                        <th>Total Qty</th>
                                    @endif
                                    @if ($hasTotalWeight)
                                        <th>Total Weight</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if ($packingSlip->packing_slip_item && count($packingSlip->packing_slip_item) > 0)
                                    @php
                                        $totalQuantity = 0;
                                        $totalQty = 0;
                                        $totalWeight = 0;
                                        $totalCarton = 0;
                                        $totalPacket = 0;
                                        $cartonNumbers = [];
                                    @endphp
                                    @foreach ($packingSlip->packing_slip_item as $index => $item)
                                        @php
                                            $totalQuantity += floatval($item->wt_pc ?? 0);
                                            $totalQty += floatval($item->total_qty ?? 0);
                                            $totalWeight += floatval($item->total_weight ?? 0);
                                            $totalPacket += floatval($item->total_packet ?? 0);
                                            if (!empty($item->no_of_carton)) {
                                                $cartonNumbers[] = $item->no_of_carton;
                                            }
                                            $prodPartNo = $item->product?->part_no ?? $item->product_code ?? '--';
                                            $prodName = $item->product ? ($item->product->product_name ?: $item->product->product_description) : ($item->product_name ?? '--');
                                            $custItemInfo = '';
                                            $psCustomer = $packingSlip->customer ?? ($packingSlip->sales_order->customer ?? null);
                                            if ($psCustomer && $psCustomer->customer_name) {
                                                $cItem = \App\Models\ProductCustomerItem::where('company_id', $packingSlip->company_id ?? $item->company_id)
                                                    ->where('product_id', $item->product_id)
                                                    ->where('customer_name', $psCustomer->customer_name)
                                                    ->whereNull('deleted_at')
                                                    ->first();
                                                if ($cItem) {
                                                    $custCode = trim($cItem->customer_item_code ?: ($cItem->customer_code ?? ''));
                                                    $custDesc = trim($cItem->customer_item_description ?? '');
                                                    $custParts = array_filter([$custCode, $custDesc]);
                                                    $custItemInfo = implode(' ', $custParts);
                                                }
                                            }
                                            $displayCode = $prodPartNo;
                                            if ($isDhruvik && !empty($custItemInfo)) {
                                                $displayCode .= ' (' . $custItemInfo . ')';
                                            } elseif (empty($prodPartNo) || $prodPartNo === '--') {
                                                $displayCode = $item->product_code ?? '--';
                                                if (!$isDhruvik && !empty($displayCode)) {
                                                    $displayCode = preg_replace('/\s*\([^)]*\)\s*$/', '', $displayCode);
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $displayCode }}</td>
                                            <td>{{ $prodName }}</td>
                                            @if ($hasHsn)
                                                <td>{{ $item->hsn_code ?? '--' }}</td>
                                            @endif
                                            <td>{{ $item->unit ?? '--' }}</td>
                                            <td>{{ number_format($item->wt_pc ?? 0, 2) }}</td>
                                            @if ($hasCarton)
                                                <td>{{ number_format($item->no_of_carton ?? 0, 2) }}</td>
                                            @endif
                                            @if ($hasQtyPc)
                                                <td>{{ number_format($item->qty_pc ?? 0, 2) }}</td>
                                            @endif
                                            @if ($hasTotalPacket)
                                                <td>{{ number_format($item->total_packet ?? 0, 2) }}</td>
                                            @endif
                                            @if ($hasQtyCtn)
                                                <td>{{ number_format($item->qty_ctn ?? 0, 2) }}</td>
                                            @endif
                                            @if ($hasActualWtCtn)
                                                <td>{{ $item->actual_wt_ctn ?? 0 }}</td>
                                            @endif
                                            @if ($hasBoxNo)
                                                <td>{{ $item->box_no ?? '--' }}</td>
                                            @endif
                                            @if ($hasTotalQty)
                                                <td>{{ number_format($item->total_qty ?? 0, 2) }}</td>
                                            @endif
                                            @if ($hasTotalWeight)
                                                <td>{{ number_format($item->total_weight ?? 0, 2) }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                    {{-- Totals Row --}}
                                    @php
                                        $colspan = 3; // Sr. No., Product Code, Product Name
                                        if ($hasHsn) {
                                            $colspan++;
                                        }
                                        $colspan++; // Unit
                                    @endphp
                                    <tr class="table-info fw-bold">
                                        <td colspan="{{ $colspan }}" class="text-end">
                                            <strong>Total:</strong>
                                        </td>
                                        <td><strong>{{ number_format($totalQuantity, 2) }}</strong></td>
                                        @if ($hasCarton)
                                            <td>
                                                <strong>{{ number_format($packingSlip->total_no_of_carton ?? 0, 2) }}</strong>
                                            </td>
                                        @endif

                                        @if ($hasQtyPc)
                                            <td></td>
                                        @endif
                                        @if ($hasTotalPacket)
                                            <td><strong>{{ number_format($totalPacket, 2) }}</strong></td>
                                        @endif
                                        @if ($hasQtyCtn)
                                            <td></td>
                                        @endif
                                        @if ($hasActualWtCtn)
                                            <td></td>
                                        @endif
                                        @if ($hasBoxNo)
                                            <td></td>
                                        @endif
                                        @if ($hasTotalQty)
                                            <td><strong>{{ number_format($totalQty, 2) }}</strong></td>
                                        @endif
                                        @if ($hasTotalWeight)
                                            <td><strong>{{ number_format($totalWeight, 2) }}</strong></td>
                                        @endif
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="13" class="text-center">No items found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                @if (
                    $modules['currentGuard'] === 'admin_software' ||
                        ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
                    <br />
                    <div class="divider">
                        <div class="divider-text">
                            <h3>Additional Information</h3>
                        </div>
                    </div>
                    @if ($packingSlip->sales_order)
                        <div class="col-md-3 col-sm-12 mb-2">
                            <b>Sales Order Customer:</b>
                            <span>{{ $packingSlip->sales_order->customer->customer_name ?? '--' }}</span>
                        </div>
                    @endif
                    @if ($packingSlip->customer && $packingSlip->customer->gstin)
                        <div class="col-md-3 col-sm-12 mb-2">
                            <b>GSTIN:</b>
                            <span>{{ $packingSlip->customer->gstin ?? '--' }}</span>
                        </div>
                    @endif
                    @if ($packingSlip->price_list_id)
                        <div class="col-md-3 col-sm-12 mb-2">
                            <b>Price List ID:</b>
                            <span>{{ $packingSlip->price_list_id ?? '--' }}</span>
                        </div>
                    @endif
                    @if ($packingSlip->created_at)
                        <div class="col-md-3 col-sm-12 mb-2">
                            <b>Created At:</b>
                            <span>{{ $packingSlip->created_at ? \Carbon\Carbon::parse($packingSlip->created_at)->format('d-m-Y H:i:s') : '--' }}</span>
                        </div>
                    @endif
                    @if ($packingSlip->updated_at)
                        <div class="col-md-3 col-sm-12 mb-2">
                            <b>Updated At:</b>
                            <span>{{ $packingSlip->updated_at ? \Carbon\Carbon::parse($packingSlip->updated_at)->format('d-m-Y H:i:s') : '--' }}</span>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection

@section('page_leavel_script')
@endsection

@push('page_scripts')
@endpush
