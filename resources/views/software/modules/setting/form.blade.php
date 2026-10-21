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

    <style>
        .ck-editor__editable_inline {
            min-height: 150px;
        }
    </style>
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
        {{-- <h5 class="card-header"></h5> --}}
        <div class="card-body">
            <form
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset
                <div class="row">
                    {{-- Company --}}
                    @if (!$company_id)
                        <div class="col-md-4 col-sm-12">
                            <label class="form-label">Select Company</label>
                            <select name="company_id" id="company_id"
                                class="form-control @error('company_id') is-invalid @enderror search_by_company select2"
                                data-append="search_by_company"
                                data-selectedCompanyId="{{ isset($edit) && $edit?->company_id ? $edit?->company_id : '' }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('company_id', $edit->company_id ?? '') ? '' : 'selected' }}>
                                    Select Company
                                </option>
                            </select>
                            @error('company_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        @else
                            <input type="hidden" class="form-control search_by_company" name="company_id"
                                   value="{{ $company_id }}" />
                        @endif
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label" for="point"> Point <span class="text-danger">*</span> </label>
                            <div class="input-group">
                                <span class="input-group-text">Per</span>
                                <input id="point" type="number"
                                    class="form-control @error('point') is-invalid @enderror" name="point"
                                    value="{{ (isset($edit) && isset($edit?->point)) ? $edit?->point : old('point') }}"
                                    placeholder="Point" min="1">
                                <span class="input-group-text">Point</span>
                            </div>
                            @error('point')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label" for="rupees"> Rupees <span class="text-danger">*</span> </label>
                            <div class="input-group">
                                <input id="rupees" type="number"
                                    class="form-control @error('rupees') is-invalid @enderror" name="rupees"
                                    value="{{ (isset($edit) && isset($edit?->rupees)) ? $edit?->rupees : old('rupees') }}"
                                    placeholder="Rupees" min="0">
                                <span class="input-group-text">Rupees</span>
                            </div>
                            @error('rupees')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label" for="min_amount">Withdraw Request Min. Amount <span class="text-danger">*</span> </label>
                            <div class="input-group">
                                <input id="min_amount" type="number" class="form-control @error('min_amount') is-invalid @enderror" name="min_amount"
                                value="{{ (isset($edit) && isset($edit?->min_amount)) ? $edit?->min_amount : old('min_amount') }}"
                                placeholder="Min. Amount" min="1">
                                <span class="input-group-text">Min. Amount</span>
                            </div>
                            @error('min_amount')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label" for="max_amount"> Withdraw Request Max. Amount <span class="text-danger">*</span> </label>
                            <div class="input-group">
                                <input id="max_amount" type="number" class="form-control @error('max_amount') is-invalid @enderror" name="max_amount"
                                value="{{ (isset($edit) && isset($edit?->max_amount)) ? $edit?->max_amount : old('max_amount') }}"
                                placeholder="Max. Amount" min="0">
                                <span class="input-group-text">Max. Amount</span>
                            </div>
                            @error('max_amount')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>



                    <div class="divider">
                        <hr />
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-success mt-1 mb-1">
                                {{ isset($edit) ? 'Update' : 'Submit' }}
                            </button>
                            <a href="{{ route($route . '.index') }}" class="btn btn-danger mt-1 mb-1">Cancel</a>
                        </div>
                    </div>
                </div>
            </form>
            @if (session('importErrors'))
                <ul class="text-danger">
                    @foreach (session('importErrors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <ul>
                        @if (session('importRecords'))
                            @foreach (session('importRecords') as $record)
                                <li class="{{ $record->status == 'success' ? 'text-success' : 'text-danger' }}">
                                    {{ $record->message }}</li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('page_scripts')
@include('utils.getCompany')
<script>
$(document).ready(function () {
    $('input[type=number]').each(function () {
        $(this).css({
            '-moz-appearance': 'textfield',
            '-webkit-appearance': 'none',
            'margin': '0'
        });
    });

    // For Webkit browsers (Chrome, Safari, Edge)
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            input[type=number]::-webkit-inner-spin-button,
            input[type=number]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            input[type=number] {
                -moz-appearance: textfield; /* Firefox */
            }
        `)
        .appendTo('head');
});
</script>
@endpush
