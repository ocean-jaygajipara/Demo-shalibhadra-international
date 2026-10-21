@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
    // dd($modules);
    $maring_bottom = 'mb-3';
@endphp
@section('title', $page_title)


@section('page_leavel_style')

    <link rel="stylesheet" href="{{ asset('software/vendor/libs/bs-stepper/bs-stepper.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css" />

    <style>
        .select2-container {
            display: block !important;
        }

        .ck-editor__editable_inline {
            min-height: 150px;
        }

        .bootstrap-tagsinput {
            width: 100%;
            min-height: 40px;
            padding: 6px 10px;
            line-height: 22px;
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            background: #fff;
        }

        .bootstrap-tagsinput .tag {
            margin-right: 2px;
            color: white;
            background-color: #29c3c0;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }
    </style>

@endsection

@section('content')
    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                [
                    'title' => isset($edit) && $edit?->id ? 'Edit ' . $page_title : 'Create ' . $page_title,
                    'url' => '',
                ],
            ],
            'route' => $route,
            'show_add_btn' => false,
            'show_back_btn' => true,
        ])
    </div>

    <!-- Default -->
    <div class="row">
        <!-- Default Wizard -->
        <div class="col-12 mb-6">
            <div class="card p-3">
                <h4 class="mb-0">Company name :- {{ $edit?->company_name ?? '' }}</h4>
            </div>
        </div>
        <div class="col-12 mb-6">
            <div class="bs-stepper wizard-numbered mt-2">
                <div class="bs-stepper-header table-responsive">
                    <div class="step" data-target="#company-information">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">Company Information</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="icon-base ti tabler-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#prefix-title">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">Prefix and Title</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="icon-base ti tabler-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#Product-Related-Settings">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">Product Related Settings</span>
                            </span>
                        </button>
                    </div>

                    <div class="line">
                        <i class="icon-base ti tabler-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#Image-Setting">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">4</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">Image Setting</span>
                            </span>
                        </button>
                    </div>

                    <div class="line">
                        <i class="icon-base ti tabler-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#General-Setting">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">5</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">General Setting</span>
                            </span>
                        </button>
                    </div>

                </div>
                <div class="bs-stepper-content">
                    <form id="CompanyDetailsForm" action="{{ route($route . '.update', [$edit?->id]) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @isset($edit)
                            @method('PUT')
                        @endisset
                        <!-- Company Information -->
                        <div id="company-information" class="content">
                            <div class="row g-6 {{ $maring_bottom }}">
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="gst_no">GST No</label>
                                    <input type="text" id="gst_no" name="gst_no"
                                        class="form-control @error('gst_no') is-invalid @enderror"
                                        value="{{ $edit->gst_no }}" placeholder="GST No" autocomplete="gst_no" />
                                    @error('gst_no')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="lut_number">LUT Number</label>
                                    <input type="text" id="lut_number" name="lut_number"
                                        class="form-control @error('lut_number') is-invalid @enderror"
                                        value="{{ $edit->lut_number }}" placeholder="LUT Number" autocomplete="lut_number" />
                                    @error('lut_number')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="company_name">Company Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="company_name" name="company_name"
                                        class="form-control @error('company_name') is-invalid @enderror"
                                        value="{{ $edit->company_name }}" placeholder="Company Name" />
                                    @error('company_name')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="person_name">Person Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="person_name" name="person_name"
                                        class="form-control @error('person_name') is-invalid @enderror"
                                        value="{{ $edit->person_name }}" placeholder="Person Name" />
                                    @error('person_name')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="whatsapp_number">Whatsapp Number <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="whatsapp_number" name="whatsapp_number"
                                        class="form-control @error('whatsapp_number') is-invalid @enderror"
                                        value="{{ $edit->whatsapp_number }}" maxlength="10"
                                        placeholder="Whatsapp Number" />
                                    @error('whatsapp_number')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="email">Email <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="email" name="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        value="{{ $edit->email }}" placeholder="Email" />
                                    @error('email')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>



                                <div class="col-md-6 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="bank_details">Bank Details</label>
                                    <textarea name="bank_details" id="bank_details"
                                        class="ckeditor_common_cls form-control @error('bank_details') is-invalid @enderror" placeholder="Bank Details">{{ $edit->bank_details }}</textarea>
                                    @error('bank_details')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="address">Address</label>
                                    <textarea name="address" id="address"
                                        class="ckeditor_common_cls form-control @error('address') is-invalid @enderror" placeholder="Address">{{ $edit->address }}</textarea>
                                    @error('address')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="pan_card">Pan Card</label>
                                    <input type="text" id="pan_card" name="pan_card"
                                        class="form-control @error('pan_card') is-invalid @enderror"
                                        value="{{ $edit->pan_card }}" placeholder="Pan Card" />
                                    @error('pan_card')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="pincode">Pincode</label>
                                    <input
                                        type="text"
                                        id="pincode"
                                        name="pincode"
                                        class="form-control @error('pincode') is-invalid @enderror"
                                        value="{{ old('pincode', $edit->pincode ?? '') }}"
                                        placeholder="Enter 6-digit pincode"
                                        maxlength="6"
                                        pattern="[1-9][0-9]{5}"
                                        inputmode="numeric"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,6);"
                                    >
                                    @error('pincode')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="mobile_min">Mobile Number Min Length</label>
                                    <input type="number" id="mobile_min" name="mobile_min"
                                        class="form-control @error('mobile_min') is-invalid @enderror"
                                        value="{{ old('mobile_min', $edit->mobile_min ?? '') }}"
                                        placeholder="Enter length" min="0" step="1"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    @error('mobile_min')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="mobile_max">Mobile Number Max Length</label>
                                    <input type="number" id="mobile_max" name="mobile_max"
                                        class="form-control @error('mobile_max') is-invalid @enderror"
                                        value="{{ old('mobile_max', $edit->mobile_max ?? '') }}"
                                        placeholder="Enter length" min="0" step="1"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    @error('mobile_max')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="order_view_format">Order View Format</label>
                                    <select id="order_view_format" name="order_view_format"
                                        class="form-select select2 @error('order_view_format') is-invalid @enderror">
                                        @foreach ($order_view_format_arr as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('order_view_format', $edit->order_view_format ?? '') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('order_view_format')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12{{ $maring_bottom }}">
                                    <label class="form-label" for="quotation_view_format">Quotation View Format</label>
                                    <select id="quotation_view_format" name="quotation_view_format"
                                        class="form-select select2 @error('quotation_view_format') is-invalid @enderror">
                                        @foreach ($quotation_view_format_arr as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('quotation_view_format', $edit->quotation_view_format ?? '') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('quotation_view_format')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="col-md-4 col-sm-12{{ $maring_bottom }}">
                                    <label class="form-label" for="purchaseOrder_view_format">PurchaseOrder View
                                        Format</label>
                                    <select id="purchaseOrder_view_format" name="purchaseOrder_view_format"
                                        class="form-select select2 @error('purchaseOrder_view_format') is-invalid @enderror">
                                        @foreach ($purchaseOrder_view_format_arr as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('purchaseOrder_view_format', $edit->purchaseOrder_view_format ?? '') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('purchaseOrder_view_format')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="col-md-4 col-sm-12{{ $maring_bottom }}">
                                    <label class="form-label" for="dispatch_view_format">Dispatch View Format</label>
                                    <select id="dispatch_view_format" name="dispatch_view_format"
                                        class="form-select select2 @error('dispatch_view_format') is-invalid @enderror">
                                        <option value="1" {{ $edit->dispatch_view_format == 1 ? 'selected' : '' }}>V1
                                        </option>
                                        <option value="2" {{ $edit->dispatch_view_format == 2 ? 'selected' : '' }}>V2
                                        </option>
                                    </select>
                                    @error('dispatch_view_format')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="invoice_view_format">Invoice View Format</label>
                                    <select id="invoice_view_format" name="invoice_view_format"
                                        class="form-select select2 @error('invoice_view_format') is-invalid @enderror">
                                        @foreach ($invoice_view_format_arr as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('invoice_view_format', $edit->invoice_view_format ?? '') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('invoice_view_format')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-12 d-flex justify-content-between mt-3">
                                    <a href="{{ route('company.index') }}"
                                        class="btn btn-label-secondary btn-prev waves-effect" disabled="">
                                        <i class="menu-icon ti ti-chevrons-left"></i>
                                        <span class="align-middle d-sm-inline-block d-none">Back</span>
                                    </a>
                                    <button type="button" class="btn btn-primary btn-next waves-effect waves-light"><span
                                            class="align-middle d-sm-inline-block d-none me-sm-2">Next</span> <i
                                            class="icon-base ti tabler-arrow-right icon-xs"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Prefix Title -->
                        <div id="prefix-title" class="content">
                            <div class="row g-6 {{ $maring_bottom }}">
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="order_prefix">Order Prefix</label>
                                    <input type="text" id="order_prefix" name="order_prefix"
                                        class="form-control @error('order_prefix') is-invalid @enderror"
                                        placeholder="Order Prefix" value="{{ $edit->order_prefix }}">
                                    @error('order_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="order_title">Order Title</label>
                                    <input type="text" id="order_title" name="order_title"
                                        class="form-control @error('order_title') is-invalid @enderror"
                                        placeholder="Order Title" value="{{ $edit->order_title }}">
                                    @error('order_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="order_view_color">Order View Color</label>
                                    <input type="color" id="order_view_color" name="order_view_color"
                                        class="form-control @error('order_view_color') is-invalid @enderror"
                                        placeholder="Order View Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit->order_view_color }}">
                                    @error('order_view_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="quotation_prefix">Quotation Prefix</label>
                                    <input type="text" id="quotation_prefix" name="quotation_prefix"
                                        class="form-control @error('quotation_prefix') is-invalid @enderror"
                                        placeholder="Quotation Prefix" value="{{ $edit->quotation_prefix }}">
                                    @error('quotation_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="quotation_title">Quotation Title</label>
                                    <input type="text" id="quotation_title" name="quotation_title"
                                        class="form-control @error('quotation_title') is-invalid @enderror"
                                        placeholder="Quotation Title" value="{{ $edit->quotation_title }}">
                                    @error('quotation_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="quotation_view_color">Quotation View Color</label>
                                    <input type="color" id="quotation_view_color" name="quotation_view_color"
                                        class="form-control @error('quotation_view_color') is-invalid @enderror"
                                        placeholder="Quotation View Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit->quotation_view_color }}">
                                    @error('quotation_view_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="dispatch_prefix">Dispatch Prefix</label>
                                    <input type="text" id="dispatch_prefix" name="dispatch_prefix"
                                        class="form-control @error('dispatch_prefix') is-invalid @enderror"
                                        placeholder="Dispatch Prefix" value="{{ $edit->dispatch_prefix }}">
                                    @error('dispatch_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="dispatch_title">Dispatch Title</label>
                                    <input type="text" id="dispatch_title" name="dispatch_title"
                                        class="form-control @error('dispatch_title') is-invalid @enderror"
                                        placeholder="Dispatch Title" value="{{ $edit->dispatch_title }}">
                                    @error('dispatch_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="dispatch_view_color">Dispatch View Color</label>
                                    <input type="color" id="dispatch_view_color" name="dispatch_view_color"
                                        class="form-control @error('dispatch_view_color') is-invalid @enderror"
                                        placeholder="Dispatch View Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit->dispatch_view_color }}">
                                    @error('dispatch_view_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="packing_slip_prefix">Packing Slip Prefix</label>
                                    <input type="text" id="packing_slip_prefix" name="packing_slip_prefix"
                                        class="form-control @error('packing_slip_prefix') is-invalid @enderror"
                                        placeholder="Packing Slip Prefix" value="{{ $edit->packing_slip_prefix }}">
                                    @error('packing_slip_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="packing_slip_title">Packing Slip Title</label>
                                    <input type="text" id="packing_slip_title" name="packing_slip_title"
                                        class="form-control @error('packing_slip_title') is-invalid @enderror"
                                        placeholder="Dispatch Title" value="{{ $edit->packing_slip_title }}">
                                    @error('packing_slip_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="packing_slip_view_color">Packing Slip View
                                        Color</label>
                                    <input type="color" id="packing_slip_view_color" name="packing_slip_view_color"
                                        class="form-control @error('packing_slip_view_color') is-invalid @enderror"
                                        placeholder="Dispatch View Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit->packing_slip_view_color }}">
                                    @error('packing_slip_view_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="vendor_prefix">Vendor Prefix</label>
                                    <input type="text" id="vendor_prefix" name="vendor_prefix"
                                        class="form-control @error('vendor_prefix') is-invalid @enderror"
                                        placeholder="Vendor Prefix" value="{{ $edit->vendor_prefix }}">
                                    @error('vendor_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="vendor_title">Vendor Title</label>
                                    <input type="text" id="vendor_title" name="vendor_title"
                                        class="form-control @error('vendor_title') is-invalid @enderror"
                                        placeholder="Vendor Title" value="{{ $edit->vendor_title }}">
                                    @error('vendor_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="vendor_view_color">Vendor View Color</label>
                                    <input type="color" id="vendor_view_color" name="vendor_view_color"
                                        class="form-control @error('vendor_view_color') is-invalid @enderror"
                                        placeholder="Vendor View Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit->vendor_view_color }}">
                                    @error('vendor_view_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="purchase_order_prefix">Purchase Order Prefix</label>
                                    <input type="text" id="purchase_order_prefix" name="purchase_order_prefix"
                                        class="form-control @error('purchase_order_prefix') is-invalid @enderror"
                                        placeholder="Purchase Order Prefix" value="{{ $edit->purchase_order_prefix }}">
                                    @error('purchase_order_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="purchase_order_title">Purchase Order Title</label>
                                    <input type="text" id="purchase_order_title" name="purchase_order_title"
                                        class="form-control @error('purchase_order_title') is-invalid @enderror"
                                        placeholder="Purchase Order Title" value="{{ $edit->purchase_order_title }}">
                                    @error('purchase_order_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="purchase_order_color">Purchase Order Color</label>
                                    <input type="color" id="purchase_order_color" name="purchase_order_color"
                                        class="form-control @error('purchase_order_color') is-invalid @enderror"
                                        placeholder="Purchase Order Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit->purchase_order_color }}">
                                    @error('purchase_order_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="inward_prefix">Inward Prefix</label>
                                    <input type="text" id="inward_prefix" name="inward_prefix"
                                        class="form-control @error('inward_prefix') is-invalid @enderror"
                                        placeholder="Inward Prefix" value="{{ $edit->inward_prefix }}">
                                    @error('inward_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="inward_title">Inward Title</label>
                                    <input type="text" id="inward_title" name="inward_title"
                                        class="form-control @error('inward_title') is-invalid @enderror"
                                        placeholder="Inward Title" value="{{ $edit->inward_title }}">
                                    @error('inward_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="inward_color">Inward Color</label>
                                    <input type="color" id="inward_color" name="inward_color"
                                        class="form-control @error('inward_color') is-invalid @enderror"
                                        placeholder="Inward Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit->inward_color }}">
                                    @error('inward_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="job_work_order_prefix">job Work Order Prefix</label>
                                    <input type="text" id="job_work_order_prefix" name="job_work_order_prefix"
                                        class="form-control @error('job_work_order_prefix') is-invalid @enderror"
                                        placeholder="job Work Order Prefix" value="{{ $edit?->company_details?->job_work_order_prefix }}">
                                    @error('job_work_order_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="job_work_order_title">job Work Order Title</label>
                                    <input type="text" id="job_work_order_title" name="job_work_order_title"
                                        class="form-control @error('job_work_order_title') is-invalid @enderror"
                                        placeholder="job Work Order Title" value="{{ $edit?->company_details?->job_work_order_title }}">
                                    @error('job_work_order_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="job_work_order_color">job Work Order Color</label>
                                    <input type="color" id="job_work_order_color" name="job_work_order_color"
                                        class="form-control @error('job_work_order_color') is-invalid @enderror"
                                        placeholder="job Work Order Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->job_work_order_color }}">
                                    @error('job_work_order_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="job_work_issue_prefix">job Work Issue Prefix</label>
                                    <input type="text" id="job_work_issue_prefix" name="job_work_issue_prefix"
                                        class="form-control @error('job_work_issue_prefix') is-invalid @enderror"
                                        placeholder="job Work Issue Prefix" value="{{ $edit?->company_details?->job_work_issue_prefix }}">
                                    @error('job_work_issue_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="job_work_issue_title">job Work Issue Title</label>
                                    <input type="text" id="job_work_issue_title" name="job_work_issue_title"
                                        class="form-control @error('job_work_issue_title') is-invalid @enderror"
                                        placeholder="job Work Issue Title" value="{{ $edit?->company_details?->job_work_issue_title }}">
                                    @error('job_work_issue_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="job_work_issue_color">job Work Issue Color</label>
                                    <input type="color" id="job_work_issue_color" name="job_work_issue_color"
                                        class="form-control @error('job_work_issue_color') is-invalid @enderror"
                                        placeholder="job Work Issue Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->job_work_issue_color }}">
                                    @error('job_work_issue_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="job_work_receive_prefix">job Work Receive Prefix</label>
                                    <input type="text" id="job_work_receive_prefix" name="job_work_receive_prefix"
                                        class="form-control @error('job_work_receive_prefix') is-invalid @enderror"
                                        placeholder="job Work Receive Prefix" value="{{ $edit?->company_details?->job_work_receive_prefix }}">
                                    @error('job_work_receive_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="job_work_receive_title">job Work Receive Title</label>
                                    <input type="text" id="job_work_receive_title" name="job_work_receive_title"
                                        class="form-control @error('job_work_receive_title') is-invalid @enderror"
                                        placeholder="job Work Receive Title" value="{{ $edit?->company_details?->job_work_receive_title }}">
                                    @error('job_work_receive_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="job_work_receive_color">job Work Receive Color</label>
                                    <input type="color" id="job_work_receive_color" name="job_work_receive_color"
                                        class="form-control @error('job_work_receive_color') is-invalid @enderror"
                                        placeholder="job Work Receive Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->job_work_receive_color }}">
                                    @error('job_work_receive_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="qc_inspection_prefix">QC Inspection Prefix</label>
                                    <input type="text" id="qc_inspection_prefix" name="qc_inspection_prefix"
                                        class="form-control @error('qc_inspection_prefix') is-invalid @enderror"
                                        placeholder="QC Inspection Prefix" value="{{ $edit?->company_details?->qc_inspection_prefix }}">
                                    @error('qc_inspection_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="qc_inspection_title">QC Inspection Title</label>
                                    <input type="text" id="qc_inspection_title" name="qc_inspection_title"
                                        class="form-control @error('qc_inspection_title') is-invalid @enderror"
                                        placeholder="QC Inspection Title" value="{{ $edit?->company_details?->qc_inspection_title }}">
                                    @error('qc_inspection_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="qc_inspection_color">QC Inspection Color</label>
                                    <input type="color" id="qc_inspection_color" name="qc_inspection_color"
                                        class="form-control @error('qc_inspection_color') is-invalid @enderror"
                                        placeholder="QC Inspection Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->qc_inspection_color }}">
                                    @error('qc_inspection_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="invoice_prefix">Invoice Prefix</label>
                                    <input type="text" id="invoice_prefix" name="invoice_prefix"
                                        class="form-control @error('invoice_prefix') is-invalid @enderror"
                                        placeholder="Invoice Prefix" value="{{ $edit?->company_details?->invoice_prefix }}">
                                    @error('invoice_prefix')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="invoice_title">Invoice Title</label>
                                    <input type="text" id="invoice_title" name="invoice_title"
                                        class="form-control @error('invoice_title') is-invalid @enderror"
                                        placeholder="Invoice Title" value="{{ $edit?->company_details?->invoice_title }}">
                                    @error('invoice_title')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="invoice_color">Invoice Color</label>
                                    <input type="color" id="invoice_color" name="invoice_color"
                                        class="form-control @error('invoice_color') is-invalid @enderror"
                                        placeholder="Invoice Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->invoice_color }}">
                                    @error('invoice_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-label-secondary btn-prev waves-effect">
                                        <i class="icon-base ti tabler-arrow-left icon-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">Previous</span>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next waves-effect waves-light"><span
                                            class="align-middle d-sm-inline-block d-none me-sm-2">Next</span> <i
                                            class="icon-base ti tabler-arrow-right icon-xs"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Product Related Settings -->
                        <div id="Product-Related-Settings" class="content">
                            <div class="row g-6 {{ $maring_bottom }}">

                                <div class="col-md-3 col-sm-4 {{ $maring_bottom }}">
                                    <label class="form-label" for="plan">Select Quantity</label>
                                    <select id="quantity"
                                        class="form-select select2 @error('quantity') is-invalid @enderror"
                                        name="quantity">

                                        @foreach ($quantity_type_arr as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('quantity', $edit->quantity ?? '') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach

                                    </select>
                                    @error('quantity')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-3 col-sm-4 {{ $maring_bottom }}">
                                    <label class="form-label" for="plan">Select Tax Type</label>
                                    <select id="tax_type"
                                        class="form-select select2 @error('tax_type') is-invalid @enderror"
                                        name="tax_type">

                                        @foreach ($tax_type_arr as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('tax_type', $edit->tax_type ?? '') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach

                                    </select>
                                    @error('tax_type')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-3 col-sm-4 {{ $maring_bottom }}">
                                    <label class="form-label" for="plan">Stock Check</label>
                                    <select id="stock_check"
                                        class="form-select select2 @error('stock_check') is-invalid @enderror"
                                        name="stock_check">

                                        @foreach ($stock_check_arr as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('stock_check', $edit->stock_check ?? '') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach

                                    </select>
                                    @error('stock_check')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-3 col-sm-4 {{ $maring_bottom }}">
                                    <label class="form-label" for="plan">Account effect By</label>
                                    <select id="account_effect_by"
                                        class="form-select select2 @error('stock_check') is-invalid @enderror"
                                        name="account_effect_by">

                                        @foreach ($account_effect_by_arr as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('account_effect_by', $edit->account_effect_by ?? '') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach

                                    </select>
                                    @error('account_effect_by')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-3 col-sm-4 {{ $maring_bottom }}">
                                    <label class="form-label" for="duplication_check_type">How to Check Product

                                        Duplication</label>
                                    <select id="duplication_check_type"
                                        class="form-select select2 @error('duplication_check_type') is-invalid @enderror"
                                        name="duplication_check_type">
                                        @foreach ($duplication_check_type_arr as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('duplication_check_type', $edit->company_details->duplication_check_type ?? '') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach

                                    </select>
                                    @error('duplication_check_type')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="col-md-3 col-sm-4 {{ $maring_bottom }}">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input @error('is_quantity_decimal') is-invalid @enderror"
                                            type="checkbox" id="is_quantity_decimal" name="is_quantity_decimal"
                                            {{ old('is_quantity_decimal', $edit?->company_details?->is_quantity_decimal ?? '') == 1 ? 'checked' : '' }}>

                                        <label class="form-check-label" for="is_quantity_decimal">
                                            Is Quantity Decimal?
                                        </label>

                                        @error('is_quantity_decimal')
                                            <span class="invalid-feedback d-block">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-6 {{ $maring_bottom }}">
                                    <div class="row">
                                        <label class="col-sm-3 col-form-label text-wrap">Stock Manage
                                            By</label>
                                        <div class="col-sm-3">

                                            <div class="form-check form-check-inline mt-2">
                                                <input class="form-check-input" type="radio" name="stock_manage_by"
                                                    id="inlineRadioLIFO" value="lifo"
                                                    {{ $edit->stock_manage_by == 'lifo' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="inlineRadioLIFO">LIFO</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="stock_manage_by"
                                                    id="inlineRadioFIFO" value="fifo"
                                                    {{ $edit->stock_manage_by == 'fifo' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="inlineRadioFIFO">FIFO</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>



                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-label-secondary btn-prev waves-effect">
                                        <i class="icon-base ti tabler-arrow-left icon-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">Previous</span>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next waves-effect waves-light"><span
                                            class="align-middle d-sm-inline-block d-none me-sm-2">Next</span> <i
                                            class="icon-base ti tabler-arrow-right icon-xs"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Image Setting -->
                        <div id="Image-Setting" class="content">
                            <div class="row g-6 {{ $maring_bottom }}">
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">
                                        Company Logo
                                        <small class="text-muted">minimum image size 400 x 300</small>
                                    </label>
                                    <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                        style="height: 150px; background-color: #f9f9f9;position: relative">
                                        <input type="file" class="form-control"
                                            style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                            id="company_logo" name="company_logo"
                                            onchange="previewImage(event, 'companyLogoPreview')" accept="image/*">
                                        <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                    </div>
                                    <!-- Image Preview Box -->
                                    <div style="margin-top: 10px;">
                                        <img id="companyLogoPreview" src="<?php echo $edit->company_logo && $edit->company_logo_url ? $edit->company_logo_url : '#'; ?>" alt="Image Preview"
                                            style="display: <?php echo $edit->company_logo ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                            onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                        <input type="hidden" name="company_logo_old"
                                            value="{{ $edit->company_logo }}">
                                    </div>
                                </div>


                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">
                                        White Labeling Logo
                                        <!-- <small class="text-muted">minimum image size 800 x 120</small> -->
                                    </label>
                                    <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                        style="height: 150px; background-color: #f9f9f9;position: relative">
                                        <input type="file" class="form-control"
                                            style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                            id="white_labeling_logo" name="white_labeling_logo"
                                            onchange="previewImage(event, 'whiteLabelingLogoPreview')" accept="image/*">
                                        <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                    </div>
                                    <!-- Image Preview Box -->
                                    <div style="margin-top: 10px;">
                                        <img id="whiteLabelingLogoPreview" src="<?php echo $edit->white_labeling_logo && $edit->white_labeling_logo_url ? $edit->white_labeling_logo_url : '#'; ?>" alt="Image Preview"
                                            style="display: <?php echo $edit->white_labeling_logo ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                            onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                        <input type="hidden" name="white_labeling_logo_old"
                                            value="{{ $edit->white_labeling_logo }}">
                                    </div>
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">
                                        Favicon Icon
                                        <!-- <small class="text-muted">minimum image size 800 x 120</small> -->
                                    </label>
                                    <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                        style="height: 150px; background-color: #f9f9f9;position: relative">
                                        <input type="file" class="form-control"
                                            style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                            id="company_favicon" name="company_favicon"
                                            onchange="previewImage(event, 'CompanyFaviconPreview')" accept="image/*">
                                        <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                    </div>
                                    <!-- Image Preview Box -->
                                    <div style="margin-top: 10px;">
                                        <img id="CompanyFaviconPreview" src="<?php echo $edit->company_favicon && $edit->company_favicon_url ? $edit->company_favicon_url : '#'; ?>" alt="Image Preview"
                                            style="display: <?php echo $edit->company_favicon ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                            onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                        <input type="hidden" name="company_favicon_old"
                                            value="{{ $edit->company_favicon }}">
                                    </div>
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">
                                        App Logo
                                        <small class="text-muted">minimum image size 512 x 512</small>
                                    </label>
                                    <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                        style="height: 150px; background-color: #f9f9f9;position: relative">
                                        <input type="file" class="form-control"
                                            style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                            id="app_logo" name="app_logo"
                                            onchange="previewImage(event, 'appLogoPreview')" accept="image/*">
                                        <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                    </div>
                                    <!-- Image Preview Box -->
                                    <div style="margin-top: 10px;">
                                        <img id="appLogoPreview" src="<?php echo $edit->app_logo && $edit->app_logo_url ? $edit->app_logo : '#'; ?>" alt="Image Preview"
                                            style="<?php echo $edit->app_logo ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                            onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                        <input type="hidden" name="app_logo_old" value="{{ $edit->app_logo }}">
                                    </div>
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">
                                        Order / Quotation / Dispatch Header
                                        <small class="text-muted">minimum image size 800 x 120</small>
                                    </label>
                                    <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                        style="height: 150px; background-color: #f9f9f9;position: relative">
                                        <input type="file" class="form-control"
                                            style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                            id="header_image" name="header_image"
                                            onchange="previewImage(event, 'headerimagePreview')" accept="image/*">
                                        <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                    </div>
                                    <!-- Image Preview Box -->
                                    <div style="margin-top: 10px;">
                                        <img id="headerimagePreview" src="<?php echo $edit->order_header_logo && $edit->order_header_logo_url ? $edit->order_header_logo_url : '#'; ?>" alt="Image Preview"
                                            style="<?php echo $edit->order_header_logo ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                            onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                        <input type="hidden" name="header_image_old"
                                            value="{{ $edit->order_header_logo }}">
                                    </div>
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">
                                        Order / Quotation / Dispatch Footer
                                        <small class="text-muted">minimum image size 800 x 100</small>
                                    </label>
                                    <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                        style="height: 150px; background-color: #f9f9f9;position: relative">
                                        <input type="file" class="form-control"
                                            style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                            id="footer_image" name="footer_image"
                                            onchange="previewImage(event, 'footerimagePreview')" accept="image/*">
                                        <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                    </div>
                                    <!-- Image Preview Box -->
                                    <div style="margin-top: 10px;">
                                        <img id="footerimagePreview" src="<?php echo $edit->order_footer_logo && $edit->order_footer_logo_url ? $edit->order_footer_logo_url : '#'; ?>" alt="Image Preview"
                                            style="<?php echo $edit->order_footer_logo ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                            onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                        <input type="hidden" name="footer_image_old"
                                            value="{{ $edit->order_footer_logo }}">
                                    </div>
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">
                                        Invoice Letter Head
                                        <small class="text-muted">minimum image size 400 x 300</small>
                                    </label>
                                    <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                        style="height: 150px; background-color: #f9f9f9;position: relative">
                                        <input type="file" class="form-control"
                                            style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                            id="invoice_letter_head" name="invoice_letter_head"
                                            onchange="previewImage(event, 'invoiceLetterHeadPreview')" accept="image/*">
                                        <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                    </div>
                                    <!-- Image Preview Box -->
                                    <div style="margin-top: 10px;">
                                        <img id="invoiceLetterHeadPreview" src="<?php echo $edit->invoice_letter_head && $edit->invoice_letter_head_url ? $edit->invoice_letter_head_url : '#'; ?>" alt="Image Preview"
                                            style="display: <?php echo $edit->invoice_letter_head ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                            onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                        <input type="hidden" name="invoice_letter_head_old"
                                            value="{{ $edit->invoice_letter_head }}">
                                    </div>
                                </div>

                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">
                                        Authorised Signature
                                        <small class="text-muted">minimum image size 400 x 300</small>
                                    </label>
                                    <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                        style="height: 150px; background-color: #f9f9f9;position: relative">
                                        <input type="file" class="form-control"
                                            style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                            id="authorised_signature" name="authorised_signature"
                                            onchange="previewImage(event, 'authorisedSignaturePreview')" accept="image/*">
                                        <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                    </div>
                                    <!-- Image Preview Box -->
                                    <div style="margin-top: 10px;">
                                        <img id="authorisedSignaturePreview" src="<?php echo $edit->authorised_signature && $edit->authorised_signature_url ? $edit->authorised_signature_url : '#'; ?>" alt="Image Preview"
                                            style="display: <?php echo $edit->authorised_signature ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                            onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                        <input type="hidden" name="authorised_signature_old"
                                            value="{{ $edit->authorised_signature }}">
                                    </div>
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-label-secondary btn-prev waves-effect">
                                        <i class="icon-base ti tabler-arrow-left icon-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">Previous</span>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next waves-effect waves-light"><span
                                            class="align-middle d-sm-inline-block d-none me-sm-2">Next</span> <i
                                            class="icon-base ti tabler-arrow-right icon-xs"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- General Setting -->
                        <div id="General-Setting" class="content">
                            <div class="row g-8 {{ $maring_bottom }}">

                                <div class="content-header mt-1 mb-4 {{ $maring_bottom }}">
                                    <h6 class="mb-0">Color Theme</h6>
                                    <!-- <small>Enter Your Company Information.</small> -->
                                </div>

                                <!-- Status Bar Color -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="status_bar_color">Status Bar Color</label>
                                    <input type="color" id="status_bar_color" name="status_bar_color"
                                        class="form-control @error('status_bar_color') is-invalid @enderror"
                                        placeholder="Status Bar Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->status_bar_color }}">
                                    @error('status_bar_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Title Name Color -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="title_name_color">Title Name Color</label>
                                    <input type="color" id="title_name_color" name="title_name_color"
                                        class="form-control @error('title_name_color') is-invalid @enderror"
                                        placeholder="Title Name Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->title_name_color }}">
                                    @error('title_name_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- All Icon Color -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="all_icon_color">All Icon Color</label>
                                    <input type="color" id="all_icon_color" name="all_icon_color"
                                        class="form-control @error('all_icon_color') is-invalid @enderror"
                                        placeholder="All Icon Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->all_icon_color }}">
                                    @error('all_icon_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Edittext Title Color -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="edittext_title_color">Edittext Title Color</label>
                                    <input type="color" id="edittext_title_color" name="edittext_title_color"
                                        class="form-control @error('edittext_title_color') is-invalid @enderror"
                                        placeholder="Edittext Title Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->edittext_title_color }}">
                                    @error('edittext_title_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Screen Background light Color -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="screen_background_light_color">Screen Background light
                                        Color</label>
                                    <input type="color" id="screen_background_light_color"
                                        name="screen_background_light_color"
                                        class="form-control @error('screen_background_light_color') is-invalid @enderror"
                                        placeholder="Screen Background light Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->screen_background_light_color }}">
                                    @error('screen_background_light_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Screen Background Dark Color -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="screen_background_dark_color">Screen Background Dark
                                        Color</label>
                                    <input type="color" id="screen_background_dark_color"
                                        name="screen_background_dark_color"
                                        class="form-control @error('screen_background_dark_color') is-invalid @enderror"
                                        placeholder="Screen Background Dark Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->screen_background_dark_color }}">
                                    @error('screen_background_dark_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- All Screen Header Color -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="all_screen_header_color">All Screen Header
                                        Color</label>
                                    <input type="color" id="all_screen_header_color" name="all_screen_header_color"
                                        class="form-control @error('all_screen_header_color') is-invalid @enderror"
                                        placeholder="All Screen Header Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->all_screen_header_color }}">
                                    @error('all_screen_header_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- All Screen Back Arrow Background Color -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="all_screen_back_arrow_background_color">All Screen Back
                                        Arrow Background Color</label>
                                    <input type="color" id="all_screen_back_arrow_background_color"
                                        name="all_screen_back_arrow_background_color"
                                        class="form-control @error('all_screen_back_arrow_background_color') is-invalid @enderror"
                                        placeholder="All Screen Back Back Arrow Background Color"
                                        style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->all_screen_back_arrow_background_color }}">
                                    @error('all_screen_back_arrow_background_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- All Screen Back Arrow Color -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="all_screen_back_arrow_color">All Screen Back Arrow
                                        Color</label>
                                    <input type="color" id="all_screen_back_arrow_color"
                                        name="all_screen_back_arrow_color"
                                        class="form-control @error('all_screen_back_arrow_color') is-invalid @enderror"
                                        placeholder="All Screen Back Arrow Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->all_screen_back_arrow_color }}">
                                    @error('all_screen_back_arrow_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Data List Border Color -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="data_list_border_color">Data List Border Color</label>
                                    <input type="color" id="data_list_border_color" name="data_list_border_color"
                                        class="form-control @error('data_list_border_color') is-invalid @enderror"
                                        placeholder="Data List Border Color" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->data_list_border_color }}">
                                    @error('data_list_border_color')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Login Text Color 1 -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="login_text_color_1">Login Text Color 1</label>
                                    <input type="color" id="login_text_color_1" name="login_text_color_1"
                                        class="form-control @error('login_text_color_1') is-invalid @enderror"
                                        placeholder="Login Text Color 1" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->login_text_color_1 }}">
                                    @error('login_text_color_1')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Login Text Color 2 -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="login_text_color_2">Login Text Color 1</label>
                                    <input type="color" id="login_text_color_2" name="login_text_color_2"
                                        class="form-control @error('login_text_color_2') is-invalid @enderror"
                                        placeholder="Login Text Color 1" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->login_text_color_2 }}">
                                    @error('login_text_color_2')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Background Shape Color 1 -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="background_shape_1">Background Shape 1</label>
                                    <input type="color" id="background_shape_1" name="background_shape_1"
                                        class="form-control @error('background_shape_1') is-invalid @enderror"
                                        placeholder="Background Shape 1" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->background_shape_1 }}">
                                    @error('background_shape_1')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Background Shape Color 2 -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="background_shape_2">Background Shape 2</label>
                                    <input type="color" id="background_shape_2" name="background_shape_2"
                                        class="form-control @error('background_shape_2') is-invalid @enderror"
                                        placeholder="Background Shape 2" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->background_shape_2 }}">
                                    @error('background_shape_2')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Background Shape Color 2 -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="background_shape_3">Background Shape 3</label>
                                    <input type="color" id="background_shape_3" name="background_shape_3"
                                        class="form-control @error('background_shape_3') is-invalid @enderror"
                                        placeholder="Background Shape 3" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->background_shape_3 }}">
                                    @error('background_shape_3')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Extra Color 1 -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="extra_color_1">Extra Color 1</label>
                                    <input type="color" id="extra_color_1" name="extra_color_1"
                                        class="form-control @error('extra_color_1') is-invalid @enderror"
                                        placeholder="Extra Color 1" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->extra_color_1 }}">
                                    @error('extra_color_1')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Extra Color 2 -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="extra_color_2">Extra Color 2</label>
                                    <input type="color" id="extra_color_2" name="extra_color_2"
                                        class="form-control @error('extra_color_2') is-invalid @enderror"
                                        placeholder="Extra Color 2" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->extra_color_2 }}">
                                    @error('extra_color_2')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Extra Color 3 -->
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="extra_color_3">Extra Color 3</label>
                                    <input type="color" id="extra_color_3" name="extra_color_3"
                                        class="form-control @error('extra_color_3') is-invalid @enderror"
                                        placeholder="Extra Color 3" style="width: 200px; height: 38px;"
                                        value="{{ $edit?->company_details?->extra_color_3 }}">
                                    @error('extra_color_3')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>



                                <div class="col-sm-12 {{ $maring_bottom }}">
                                    <hr class="">
                                </div>

                                <div class="content-header mt-1 mb-4 {{ $maring_bottom }}">
                                    <h6 class="mb-0">Default Settings</h6>
                                    <!-- <small>Enter Your Company Information.</small> -->
                                </div>
                                <div class="col-md-12 col-sm-12 {{ $maring_bottom }}">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value=""
                                            id="defaultCheckCopyright"
                                            {{ $edit->is_copyright_view == 'yes' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="defaultCheckCopyright"> Copyright </label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="default_password">Default Password </label>
                                    <input type="text" id="default_password" name="default_password"
                                        class="form-control @error('default_password') is-invalid @enderror"
                                        value="{{ $edit->default_password }}" />
                                    @error('default_password')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="reset_password">Reset Password</label>
                                    <input type="text" id="reset_password" name="reset_password"
                                        class="form-control @error('reset_password') is-invalid @enderror"
                                        value="{{ $edit->reset_password }}" />
                                    @error('reset_password')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <div class="form-group">
                                        <label class="form-label">Select Country <span
                                                class="text-danger">*</span></label>
                                        <select name="country_id" id="country_id"
                                            class="form-control @error('country_id') is-invalid @enderror search_by_country select2" data-append="search_by_country">
                                            <option value="" disabled selected>Select Country</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->id }}"
                                                    {{ $edit->country_id == $country->id ? 'selected' : '' }}
                                                    data-phonecode="{{ $country->code }}">{{ $country->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('country_id')
                                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>



                                <div class="col-md-3 col-sm-12 mb-2">
                                    <div class="form-group">
                                        <label class="form-label">Select State <span class="text-danger">*</span></label>
                                        <select name="state_id" id="state_id" data-name="state"
                                            class="form-control @error('state_id') is-invalid @enderror search_by_state select2 required"
                                            data-append="search_by_state" data-selectedCountryId="{{ isset($edit) && $edit?->country_id ? $edit?->country_id : old('country_id') }}"
                                            data-selectedStateId="{{ isset($edit) && $edit?->state_id ? $edit?->state_id : old('state_id') }}">

                                            <option value="" disabled
                                                {{ old('state_id', $edit->state_id ?? '') ? '' : 'selected' }}>
                                                Select State
                                            </option>
                                        </select>
                                        @error('state_id')
                                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="phonecode">Phonecode</label>
                                    <input type="text" id="phonecode" name="phonecode"
                                        class="form-control @error('phonecode') is-invalid @enderror"
                                        value="{{ $edit->phonecode }}" readonly />
                                    @error('phonecode')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label" for="per_minutes_request_current_location">Set Time to get
                                        Sales Person Location Per Time</label>
                                    <div class="input-group">
                                        <input type="number" id="per_minutes_request_current_location"
                                            name="per_minutes_request_current_location"
                                            class="form-control @error('per_minutes_request_current_location') is-invalid @enderror"
                                            value="{{ old('per_minutes_request_current_location', $edit?->company_details?->per_minutes_request_current_location ?? '') }}" />
                                        <span class="input-group-text">Minutes</span>
                                    </div>
                                    @error('per_minutes_request_current_location')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="col-md-3 col-sm-12{{ $maring_bottom }}">
                                    <label class="form-label" for="how_assign_inquiry">How to Inquiry Assign</label>
                                    <select id="how_assign_inquiry" name="how_assign_inquiry"
                                        class="form-select select2 @error('how_assign_inquiry') is-invalid @enderror">
                                        @foreach ($how_assign_inquiry_arr as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('how_assign_inquiry', $edit?->company_details?->how_assign_inquiry ?? '') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('how_assign_inquiry')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-label-secondary btn-prev waves-effect">
                                        <i class="icon-base ti tabler-arrow-left icon-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">Previous</span>
                                    </button>
                                    <button type="submit" id="submit"
                                        class="btn btn-success btn-submit waves-effect waves-light">Submit</button>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
        <!-- /Default Wizard -->
    </div>
@endsection

@section('page_leavel_script')
    <!-- Vendors JS -->
    <script src="{{ asset('software/vendor/libs/bs-stepper/bs-stepper.js') }}"></script>
    <!-- Page JS -->

    <script src="{{ asset('software/js/form-wizard-numbered.js') }}"></script>


    <script src="{{ asset('software/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('software/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.js"></script>
    <!-- <script src="{{ asset('software/js/forms-pickers.js') }}"></script> -->
@endsection

@push('page_scripts')
    @include('utils.getStateByCountry')
    <script>
        $('.search_by_country').trigger("change");
        initializeCKEditor(".ckeditor_common_cls");
        function previewImage(event, previewId) {
            const input = event.target;
            const preview = document.getElementById(previewId);
            const file = input.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
            }
        }
    </script>

    <script>
        $(document).ready(function() {

            $('#plan_from').flatpickr({
                dateFormat: "Y-m-d",
                minDate: "today",
                onChange: function(selectedDates, dateStr) {
                    if (dateStr) {
                        $('#plan_to').flatpickr('set', 'minDate', dateStr);
                    }
                }
            });

            $('#plan_to').flatpickr({
                dateFormat: "Y-m-d",
                minDate: "today",

            });

            $(document).on('change', '#country_id', function() {
                var selectedOption = $(this).find('option:selected');
                var phoneCode = selectedOption.data('phonecode');
                $('#phonecode').val(phoneCode);
            });

        });

        $('#bcc').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // stop form submit

                const input = $(this);
                const value = input.val().trim();

                if (value) {
                    input.tagsinput('add', value);
                    input.val('');
                }

                // wait a bit then submit
                setTimeout(() => {
                    $('#myForm').submit();
                }, 150);
            }
        });
    </script>
@endpush
