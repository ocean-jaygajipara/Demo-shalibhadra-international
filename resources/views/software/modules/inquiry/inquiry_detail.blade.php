@extends('software.layout.app')

@php
    $i = 0;

    $now = \Carbon\Carbon::now()->setTimezone('Asia/Kolkata')->format('Y-m-d\TH:i');

    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;
@endphp

@section('title', $page_title)

@section('page_leavel_style')

@endsection

@section('content')
    <div class="px-1">
        <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
            @include('software.inlcudes.breadcrumb', [
                'breadcrumbArray' => [
                    ['title' => $page_title, 'url' => route($route . '.index')],
                    [
                        'title' => 'Show ' . $page_title,
                        'url' => '',
                    ],
                ],
                'show_filter_btn' => false,
                'show_back_btn' => true,
            ])
        </div>
    </div>

    <div class="card my-3 mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Inquiry No:</b>
                    <span>{{ $show?->inquiry_no ?? '' }}</span>
                </div>
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Inquiry Date:</b>
                    <span>{{ $show?->inquiry_date ?? '' }}</span>
                </div>
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Inquiry Status:</b>
                    @if (isset($show?->inquiry_status) && $show?->inquiry_status)
                        <span class="p-2 rounded text-nowrap"
                            style="background: linear-gradient(45deg, {{ $show?->inquiry_status?->status_color }}, {{ $show?->inquiry_status?->status_color_2 }}); color: {{ $show?->inquiry_status?->text_color }}  ">{!! $show?->inquiry_status?->name ?? '--' !!}</span>
                        {{-- {!! $show?->inquiry_status ?? '--' !!} --}}
                    @else
                        --
                    @endif
                    {{-- <span>{{ $show?->inquiry_status_id ?? '--' }}</span> --}}
                </div>
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Followup Status:</b>
                    @if (isset($show?->followup_status_id) && $show?->followup_status_id)
                        <span class="p-2 rounded text-nowrap"
                            style="background: linear-gradient(45deg, {{ $show?->followup_status?->status_color }}, {{ $show?->followup_status?->status_color_2 ?? $show?->followup_status?->status_color }}); color: {{ $show?->followup_status?->text_color }}  ">{!! $show?->followup_status?->name ?? '--' !!}</span>
                        {{-- {!! $show?->followup_status ?? '--' !!} --}}
                    @else
                        --
                    @endif
                </div>

                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Source of inquiry:</b>
                    @if (isset($show?->followup_status_id) && $show?->source_of_inquiry)
                        <span class="text-nowrap">{!! $show?->source_of_inquiry?->name ?? '--' !!}</span>
                        {{-- {!! $show?->source_of_inquiry ?? '--' !!} --}}
                    @else
                        --
                    @endif
                </div>

                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Inquiry Created By:</b>
                    @if (isset($show?->inquiry_created_by) && $show?->createdBy)
                        <span class="text-nowrap">{!! $show?->createdBy?->name ?? '--' !!}</span>
                    @else
                        --
                    @endif
                </div>

                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Inquiry Assigned To:</b>
                    @if (isset($show?->inquiry_assigned_to) && $show?->inquiry_assigned_to && count($show?->getAssignedToNamesAttribute()))
                        {!! implode(', ', $show?->getAssignedToNamesAttribute()) !!}
                    @else
                        --
                    @endif
                </div>

                @if ($show?->requirement_details)
                    <div class="col-md-9 col-sm-12 mb-2">
                        <b>Requirement Details:</b>
                        <span>{!! $show?->requirement_details ?? '--' !!}</span>
                    </div>
                @endif

                @if ($show?->address)
                    <div class="col-md-6 col-sm-12 mb-2">
                        <b>Address:</b>
                        <span>{!! $show?->address ?? '--' !!}</span>
                    </div>
                @endif

                @if ($show?->pincode || $show?->city_id || $show?->state_id || $show?->country_id || $show?->area_id)
                    <div class="col-md-6 col-sm-12 mb-2">
                        <b>Area:</b>
                        <span>{!! $show?->area_id ?? '' !!}</span>
                        @if ($show?->pincode)
                            <span>{!! $show?->pincode . ', ' ?? '' !!}</span>
                        @endif
                        @if ($show?->city_id)
                            <span>{!! $show?->city?->name . ', ' ?? '' !!}</span>
                        @endif
                        @if ($show?->state_id)
                            <span>{!! $show?->state?->name . ', ' ?? '' !!}</span>
                        @endif
                        @if ($show?->country_id)
                            <span>{!! $show?->country?->name . ', ' ?? '' !!}</span>
                        @endif
                    </div>
                @endif

                <br />
                <div class="divider">
                    <div class="divider-text">
                        <h3>Customer Detail</h3>
                    </div>
                </div>

                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Customer Name:</b>
                    <span>{{ $show?->customer_name ?? '' }}</span>
                </div>
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Contact Person Name:</b>
                    <span>{{ $show?->contact_person_name ?? '' }}</span>
                </div>
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Mobile No:</b>
                    <span>{{ $show?->mobile_no ?? '' }}</span>
                </div>
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Whatsapp No:</b>
                    <span>{{ $show?->whatsapp_no ?? '--' }}</span>
                </div>
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Email:</b>
                    <span>{{ $show?->email ?? '--' }}</span>
                </div>

                <div class="col-md-3 col-sm-12 mb-2">
                    <b>WhatsApp Messages Sent:</b>
                    <span class="badge bg-success fs-7">{{ $show?->whatsapp_general_sent_count ?? 0 }} times</span>
                </div>

                @if($show?->last_whatsapp_general_sent_at)
                <div class="col-md-3 col-sm-12 mb-2">
                    <b>Last WhatsApp Sent At:</b>
                    <span>{{ \Carbon\Carbon::parse($show->last_whatsapp_general_sent_at)->format('d-m-Y h:i A') }}</span>
                </div>
                @endif
            </div>
            {{-- <pre class="text-wrap">
                {!! $show !!}
            </pre> --}}
        </div>
    </div>
@endsection

@section('page_leavel_script')

@endsection

@push('page_scripts')
@endpush
