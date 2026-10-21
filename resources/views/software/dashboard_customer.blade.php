@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;
@endphp

@section('title', 'Dashboard')

@section('page_leavel_style')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
@endsection

@push('push_style')
    <style>
        #map {
            height: 100vh;
            border-radius: 2px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
        }


        .nav-pills .nav-link.active {
            background-color: #5c6bc0;
            color: white;
        }

        canvas {
            max-height: 400px;
        }

        .marker-cluster-small {

            background-color: orange !important;
            color: white !important;
            border: 2px solid #FF9900;
            /* Border slightly darker orange */

        }

        .marker-cluster-small div {

            background-color: orange !important;

        }
    </style>
@endpush


@section('content')
    <div class="row">
        <div class="mb-0">
            <div class="card-header py-2 px-4 d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="card-title mb-2 mb-md-0">customer dashboard</h5>
                <div class="d-flex flex-wrap gap-3">

                </div>
            </div>
        </div>
        <div class="col-12 mb-3">
            <div class=" h-100">
                <div class="mt-3" id="inquiryStatistics">
                    <div class="p-4 text-center">
                        <p class="mb-0 fs-5">No data available. Please apply filters to see statistics.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection


