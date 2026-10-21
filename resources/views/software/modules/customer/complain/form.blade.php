@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
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
        <div class="card-body">
            <form
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <div class="row">

                    <!-- Company Name -->
                    @if (!$company_id)
                        <div class="col-md-4 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Select Company <span class="text-danger">*</span></label>
                                <select id="company_id"
                                    class="form-control select2 search_by_company @error('company_id') is-invalid @enderror"
                                    name="company_id"
                                    data-selectedCompanyId="{{ old('company_id') ?? ($edit->company_id ?? '') }}">
                                    <option value="">Select Company</option>
                                </select>

                                @error('company_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    @else
                        <input type="hidden" class="form-control search_by_company" name="company_id"
                            value="{{ $company_id }}" />
                    @endif
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Complain No <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control readonly-look  @error('complain_no') is-invalid @enderror"
                                name="complain_no" value="{{ old('complain_no', $edit->complain_no ?? '') }}"
                                placeholder="Enter Complain No" readonly id="complain_no">
                            @error('complain_no')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Customer Type -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Customer Type <span class="text-danger">*</span></label>
                            <select name="customer_type_id" id="customer_type_id"
                                class="form-control @error('customer_type_id') is-invalid @enderror search_by_customer_type select2"
                                data-append="search_by_customer_type"
                                data-selectedCustomerTypeId="{{ isset($edit) && $edit?->customer_type_id ? $edit?->customer_type_id : '' }}"
                                autofocus>
                                <option value=""> Select Customer Type</option>
                            </select>
                            @error('customer_type_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Customer Name -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id"
                                class="form-control @error('customer_id') is-invalid @enderror search_by_customer select2"
                                data-append="search_by_customer"
                                data-selectedCustomerId="{{ isset($edit) && $edit?->customer_id ? $edit?->customer_id : '' }}"
                                autofocus>
                                <option value=""> Select Customer </option>
                            </select>
                            @error('customer_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Source of Complaint -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Select Source of Complaint </label>
                            <select name="source_complain_id" id="source_of_complaint_id"
                                class="form-control @error('source_complain_id') is-invalid @enderror search_by_source_of_complaint select2"
                                data-append="search_by_source_of_complaint"
                                data-selectedSourceOfComplaintId="{{ isset($edit) && $edit?->source_complain_id ? $edit?->source_complain_id : '' }}"
                                autofocus>
                                <option value=""> Select Source of Complaint</option>
                            </select>
                            @error('source_complain_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Complaint Type -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Complaint Type <span class="text-danger">*</span></label>
                            <select name="complain_type_id" id="complain_type_id"
                                class="form-control @error('complain_type_id') is-invalid @enderror search_by_complain_type select2"
                                data-append="search_by_complain_type"
                                data-selectedComplainTypeId="{{ isset($edit) && $edit?->complain_type_id ? $edit?->complain_type_id : '' }}"
                                autofocus>
                                <option value=""> Select Complaint Type</option>
                            </select>
                            @error('complain_type_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>



                    <!-- Remark -->
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Remark</label>
                            <textarea id="remark" class="form-control autosize @error('remark') is-invalid @enderror" name="remark"
                                rows="1" autocomplete="remark" placeholder="Enter remark">{{ isset($edit) ? $edit->remark : old('remark') }}</textarea>
                            @error('remark')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <!-- Complaint Created By -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Complaint Created By <span class="text-danger">*</span></label>
                            <select name="complain_created_by_id" id="inquiry_created_by"
                                class="form-control @error('complain_created_by_id') is-invalid @enderror search_by_created_by select2"
                                data-append="search_by_created_by"
                                data-selectedInquiryCreatedBy="{{ isset($edit) && $edit?->complain_created_by_id ? $edit?->complain_created_by_id : '' }}"
                                autofocus>
                                <option value="">Complaint Created By </option>
                            </select>
                            @error('complain_created_by_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Complaint Assigned To -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Complaint Assigned To <span class="text-danger">*</span></label>
                            <select name="complain_assign_to_id" id="inquiry_assigned_to"
                                class="form-control @error('complain_assign_to_id') is-invalid @enderror search_by_assigned_to select2"
                                data-append="search_by_assigned_to"
                                data-selectedInquiryAssignedTo="{{ isset($edit) && $edit?->complain_assign_to_id ? $edit?->complain_assign_to_id : '' }}"
                                autofocus>
                                <option value=""> Select Assigned To </option>
                            </select>
                            @error('complain_assign_to_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <!-- Attachment -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Attachment (PDF, Word, Image) <small class="text-muted">(Max:
                                    10MB)</small></label>
                            <input type="file" name="image"
                                class="form-control @error('image') is-invalid @enderror"
                                accept=".jpeg,.jpg,.png,.pdf,.doc,.docx">
                            @error('image')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    @if (!empty($edit->image))
                        @php $filePath = public_path($edit->image); @endphp
                        @if (file_exists($filePath))
                            <a href="{{ asset($edit->image) }}" download>Download Attachment</a>
                        @else
                            <p>Attachment not found.</p>
                        @endif
                    @endif



                    <!-- Submit and Cancel Buttons -->
                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-success mt-1 mb-1">
                            {{ isset($edit) ? 'Update' : 'Submit' }}
                        </button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger mt-1 mb-1">Cancel</a>
                    </div>
                </div>
            </form>

        </div>
    </div>
@endsection



@push('page_scripts')
    <script src="https://cdn.jsdelivr.net/npm/autosize@4.0.2/dist/autosize.min.js">
        $(document).ready(function() {
            autosize($('.autosize'));
        });
    </script>

    @if (!isset($edit))
        @include('utils.generateComplainNo')
    @endif
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getCompanyByCustomerType')
    @include('utils.getCustomer')
    @include('utils.getSourceOfInquiry')
    @include('utils.getComplainType')
    @include('utils.getTeams')
@endpush
