@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
@endphp
@section('title', $page_title)

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
        ])
        <a class="btn btn-primary waves-effect waves-light text-white" href="{{ url()->previous() }}"> <i
                class="menu-icon ti ti-chevrons-left"></i> Back</a>
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
                    {{-- Company Name --}}
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

                    {{-- Warehouse Name --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Warehouse <span class="text-danger">*</span></label>
                            <select id="warehouse_id"
                                class="form-control select2 @error('warehouse_id') is-invalid @enderror"
                                name="warehouse_id">
                                <option value="">Select Warehouse</option>
                                <option value="warehouse_1"
                                    {{ old('warehouse_id', $edit->warehouse_id ?? '') == 'warehouse_1' ? 'selected' : '' }}>
                                    Warehouse 1</option>
                                <option value="warehouse_2"
                                    {{ old('warehouse_id', $edit->warehouse_id ?? '') == 'warehouse_2' ? 'selected' : '' }}>
                                    Warehouse 2</option>
                            </select>

                            @error('warehouse_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Product Category --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Product Category <span class="text-danger">*</span></label>
                            <select id="product_category_id"
                                class="form-control select2 @error('product_category_id') is-invalid @enderror"
                                name="product_category_id">
                                <option value="">Select Product Category</option>
                                @foreach ($productCategories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('product_category_id', $edit->product_category_id ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('product_category_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Product Sub Category --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Product Sub Category <span class="text-danger">*</span></label>
                            <select id="product_sub_category_id"
                                class="form-control select2 @error('product_sub_category_id') is-invalid @enderror"
                                name="product_sub_category_id">
                                <option value="">Select Product Sub Category</option>
                                @foreach ($productSubCategories as $subCategory)
                                    <option value="{{ $subCategory->id }}"
                                        {{ old('product_sub_category_id', $edit->product_sub_category_id ?? '') == $subCategory->id ? 'selected' : '' }}>
                                        {{ $subCategory->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('product_sub_category_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Product Name --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('product_name') is-invalid @enderror"
                                name="product_name"
                                value="{{ old('product_name', $edit->product_name ?? '') }}"
                                placeholder="Enter Product Name" />

                            @error('product_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Product Code --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Product Code <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('product_code') is-invalid @enderror"
                                name="product_code"
                                value="{{ old('product_code', $edit->product_code ?? '') }}"
                                placeholder="Enter Product Code" />

                            @error('product_code')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Available Product Stock Qty --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Available Product Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('available_stock_qty') is-invalid @enderror"
                                name="available_stock_qty"
                                value="{{ old('available_stock_qty', $edit->available_stock_qty ?? '') }}"
                                placeholder="Enter Available Stock Quantity" />

                            @error('available_stock_qty')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Min Stock --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Min Stock <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('min_stock') is-invalid @enderror"
                                name="min_stock"
                                value="{{ old('min_stock', $edit->min_stock ?? '') }}"
                                placeholder="Enter Min Stock Quantity" />

                            @error('min_stock')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Max Stock --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Max Stock <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('max_stock') is-invalid @enderror"
                                name="max_stock"
                                value="{{ old('max_stock', $edit->max_stock ?? '') }}"
                                placeholder="Enter Max Stock Quantity" />

                            @error('max_stock')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>



                    {{-- Divider --}}
                    <div class="divider">
                        <hr />
                    </div>

                    {{-- Submit Buttons --}}
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
    @include('utils.getCompany')

@endpush
