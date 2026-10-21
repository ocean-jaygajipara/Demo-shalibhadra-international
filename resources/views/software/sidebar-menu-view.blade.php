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

@section('title', 'Sidebar Menu')

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/jstree/jstree.css') }}" />
@endsection

@push('push_style')
@endpush


@section('content')

    <div class="row">
        <!-- Drag & Drop -->
        <div class="col-md-6 col-12">
            <div class="card">
                <h5 class="card-header">Sidebar Menu's</h5>
                <div class="card-body" style="max-height: 700px; overflow: scroll;">
                    <div>
                        <ul style="list-style-type: none;">
                            @if (isset($module_menu))
                                @foreach ($module_menu as $row_menu)
                                    @if ($row_menu?->name)
                                        @php
                                            $row_menu_detail = collect(
                                                (object) $row_menu->except(['main_menu_routes']),
                                            );
                                            $row_menu_detail = json_encode($row_menu_detail);
                                            // dd($row_menu_detail);
                                        @endphp
                                        {{-- {!! $row_menu_detail !!} --}}
                                        <li data-jstree='{"icon" : "{{ $row_menu?->menu_icon }}"}'>
                                            {{ $row_menu?->order_by ?? '' }} -
                                            <i class="{{ $row_menu?->menu_icon }}"></i>
                                            {{ $row_menu?->name ?? '' }}
                                            @if ($row_menu?->status == 'active')
                                                <span class='badge bg-label-success bg-glow'>Active</span>
                                            @elseif($row_menu?->status == 'inactive')
                                                <span class='badge bg-label-warning bg-glow'>In Active</span>
                                            @else
                                                <span class='badge bg-label-secondary bg-glow'>Active</span>
                                            @endif
                                            <span
                                                class="btn btn-icon btn-sm btn-label-info waves-effect main-menu menuEdit mb-2"
                                                data-rowMenuData='{{ $row_menu_detail }}'><i
                                                    class="fa-solid fa-pen-to-square"></i></span>
                                            <span class="btn btn-icon btn-sm btn-label-warning waves-effect menuDelete mb-2"
                                                data-rowMenuData='{{ $row_menu_detail }}'><i
                                                    class="fa-solid fa-trash"></i></span>

                                            @if ($row_menu?->sub_menu && count($row_menu?->sub_menu) > 0)
                                                <ul style="list-style-type: none;">
                                                    @foreach ($row_menu->sub_menu as $sub_menu)
                                                        @if ($sub_menu?->name)
                                                            @php
                                                                // $row_menu_detail = collect( (object) $sub_menu->except(['main_menu_routes']));
                                                                $sub_menu_detail = json_encode($sub_menu);
                                                                // dd($row_menu_detail);
                                                            @endphp
                                                            {{-- {!! $sub_menu_detail !!} --}}
                                                            <li data-jstree='{"icon" : "icon-base ti tabler-folder"}'>
                                                                {{ $row_menu?->order_by . '.' ?? '' }}
                                                                {{ $sub_menu?->order_by ?? '' }} -
                                                                {{ $sub_menu?->name ?? '' }}
                                                                @if ($sub_menu?->status == 'active')
                                                                    <span
                                                                        class='badge bg-label-success bg-glow'>Active</span>
                                                                @elseif($sub_menu?->status == 'inactive')
                                                                    <span class='badge bg-label-warning bg-glow'>In
                                                                        Active</span>
                                                                @else
                                                                    <span
                                                                        class='badge bg-label-secondary bg-glow'>Active</span>
                                                                @endif

                                                                <span
                                                                    class="btn btn-icon btn-sm btn-label-dark waves-effect sub-menu menuEdit mb-2"
                                                                    {{-- data-rowMenuData='{{ $row_menu_detail }}' --}}
                                                                    data-rowSubData='{{ $sub_menu_detail }}'><i
                                                                        class="fa-solid fa-pen-to-square"></i></span>
                                                                <span
                                                                    class="btn btn-icon btn-sm btn-label-warning waves-effect menuDelete mb-2"
                                                                    data-rowMenuData='{{ $sub_menu_detail }}'><i
                                                                        class="fa-solid fa-trash"></i></span>
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endif
                                    <br>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                    {{-- <div id="jstree-basic" class="overflow-auto">
                        <ul>
                            @foreach ($module_menu as $row_menu)
                                @if ($row_menu?->name)
                                    <li data-jstree='{"icon" : "{{ $row_menu?->menu_icon }}"}'>
                                        {{ $row_menu?->order_by ?? '' }} -
                                        {{ $row_menu?->name ?? '' }}
                                        <span class="main-menu" data-rowData={{ json_encode($row_menu) }}><i
                                                class="fa-solid fa-pen-to-square"></i></span>
                                        @if ($row_menu?->sub_menu && count($row_menu?->sub_menu) > 0)
                                            <ul>
                                                @foreach ($row_menu->sub_menu as $sub_menu)
                                                    @if ($sub_menu?->name)
                                                        <li data-jstree='{"icon" : "icon-base ti tabler-folder"}'>
                                                            {{ $sub_menu?->name ?? '' }}
                                                            <span class="sub-menu" data-rowData={{ json_encode($sub_menu) }}><i
                                                                    class="fa-solid fa-pen-to-square"></i></span>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div> --}}
                </div>
            </div>
        </div>
        <!-- /Drag & Drop -->

        <div class="col-md-6 col-12">
            <div class="card">
                <h5 class="card-header">New Menu and SubMenu Add Form </h5>
                <div class="card-body">

                    <form id="form-sidebar-menu" action="{{ route('sidebar.menu') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="edit_id" class="form-control" name="edit_id"
                            value="{{ old('edit_id' ?? '') }}" />
                        <div class="row">
                            <div class="col-md-6 col-sm-12 mb-3">
                                <div class="form-group">
                                    <label class="form-label">Select Platform <span class="text-danger">*</span></label>
                                    <select id="platform"
                                        class="form-control select2 @error('platform') is-invalid @enderror" name="platform"
                                        data-selectedCompanyId="{{ old('platform') ?? ($edit->platform ?? '') }}">
                                        <option value="">Select Platform</option>
                                        @foreach (['panel', 'app'] as $row_platform)
                                            <option value="{{ $row_platform }}">{{ $row_platform }}</option>
                                        @endforeach
                                    </select>

                                    @error('platform')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-12 mb-3">
                                <div class="form-group">
                                    <label class="form-label">Select Main Menu <span class="text-danger">*</span></label>
                                    <select id="main_menu_id"
                                        class="form-control select2 @error('main_menu_id') is-invalid @enderror"
                                        name="main_menu_id"
                                        data-selectedCompanyId="{{ old('main_menu_id') ?? ($edit->main_menu_id ?? '') }}">
                                        <option value="">Select Main Menu</option>
                                        @foreach ($module_menu as $row_menu)
                                            <option value="{{ $row_menu?->id }}"
                                                @if (isset($row_menu?->platform)) data-platform="{{ $row_menu?->platform }}" @endif>
                                                {{ $row_menu?->name }}</option>
                                        @endforeach
                                    </select>

                                    @error('main_menu_id')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-12 mb-3">
                                <div class="form-group">
                                    <label class="form-label">Menu or Sub Menu Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        name="name" value="{{ isset($edit) ? $edit->name : old('name') }}"
                                        placeholder="Enter Menu Or Sub Menu Name">

                                    @error('name')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-12 mb-3 div_menu_icon">
                                <div class="form-group">
                                    <label class="form-label">Menu Icon</label>
                                    <input type="text" class="form-control @error('menu_icon') is-invalid @enderror"
                                        name="menu_icon" value="{{ isset($edit) ? $edit->menu_icon : old('menu_icon') }}"
                                        placeholder="Enter icon">


                                    @error('menu_icon')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-12 mb-3 div_route_name">
                                <div class="form-group">
                                    <label class="form-label">Route Name</label>
                                    <input type="text" class="form-control @error('route_name') is-invalid @enderror"
                                        name="route_name"
                                        value="{{ isset($edit) ? $edit->route_name : old('route_name') }}"
                                        placeholder="Enter route name">


                                    @error('route_name')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-12 mb-3 div_route_name">
                                <div class="form-group">
                                    <label class="form-label">URL</label>
                                    <input type="text" class="form-control @error('url') is-invalid @enderror"
                                        name="url" value="{{ isset($edit) ? $edit->url : old('url') }}"
                                        placeholder="Enter url">


                                    @error('url')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-12 mb-3">
                                <div class="form-group">
                                    <label class="form-label">Menu Order / Sequence <span
                                            class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('order_by') is-invalid @enderror"
                                        name="order_by" value="{{ isset($edit) ? $edit->order_by : old('order_by') }}"
                                        placeholder="Set menu order" min="0">


                                    @error('order_by')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-12 mb-3">
                                <div class="form-group">
                                    <label class="form-label">Status <span class="text-danger">*</span> </label>
                                    <select class="form-control select2 w-100 @error('status') is-invalid @enderror"
                                        name="status" required>
                                        <option disabled selected>Select Status</option>
                                        @foreach (['active', 'inactive'] as $status)
                                            <option value="{{ $status }}"
                                                @if (isset($edit)) @if ($edit->status == $status) {{ 'selected' }} @endif
                                            @else @if (old('status', 'active') == $status) {{ 'selected' }} @endif
                                                @endif> {{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary btn-submit">
                                    {{ isset($edit) ? 'Update' : 'Create' }} Menu
                                </button>
                                <a class="btn btn-warning text-white" href="{{ route('sidebar.menu') }}">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_leavel_script')
    <script src="{{ asset('software/vendor/libs/jstree/jstree.js') }}"></script>
    {{-- <script src="{{ asset('software/js/extended-ui-treeview.js') }}"></script> --}}
@endsection

@push('page_scripts')
    <script>
        $(function() {
            var theme = $('html').hasClass('light-style') ? 'default' : 'default-dark',
                basicTree = $('#jstree-basic'),
                dragDrop = $('#jstree-drag-drop');
            // Basic
            // --------------------------------------------------------------------
            if (basicTree.length) {
                basicTree.jstree({
                    core: {
                        themes: {
                            name: theme
                        }
                    }
                });
            }

            // Drag Drop
            // --------------------------------------------------------------------
            if (dragDrop.length) {
                dragDrop.jstree({
                    core: {
                        themes: {
                            name: theme
                        },
                        check_callback: true,
                        /*
                        data: [{
                                text: 'css',
                                children: [{
                                        text: 'app.css',
                                        type: 'css'
                                    },
                                    {
                                        text: 'style.css',
                                        type: 'css'
                                    }
                                ]
                            },
                            {
                                text: 'page-two.html',
                                type: 'html'
                            }
                        ]
                            */
                    },
                    plugins: ['types', 'dnd'],
                });
            }
        });

        // Store original main menu options
        const $mainMenu = $('#main_menu_id');
        const originalOptions = $mainMenu.find('option').clone();

        $('.div_menu_icon').hide();
        $('.div_route_name').hide();

        let selectedPlatForm = null;
        $('#platform').on('change', function() {
            $('.div_menu_icon').hide();
            selectedPlatForm = $(this).val();
            if (selectedPlatForm === 'panel') {
                $('.div_menu_icon').show();
            }
            // Remove all current options
            $mainMenu.empty();

            // Filter and re-add only relevant options
            originalOptions.each(function() {
                const platform = $(this).data('platform');

                // Keep default option or matching platform
                if (!platform || platform === selectedPlatForm) {
                    $mainMenu.append($(this));
                }
            });

            // Reset value
            $mainMenu.val('').trigger('change');
            $('#main_menu_id').prop('disabled', false);
        });

        $('#main_menu_id').on('change', function() {
            $('.div_route_name').hide();
            selectedPlatForm = $(this).find('option:selected').attr('data-platform');

            if (selectedPlatForm === 'panel') {
                if ($(this).val()) {
                    $('.div_route_name').show();
                }
            }
        });

        $(".menuEdit").on('click', function() {
            let form = $("#form-sidebar-menu");
            let rawData = $(this).attr("data-rowmenudata") || $(this).attr("data-rowsubdata");

            if (!rawData) {
                toaster.error("Edit Data not found");
                return;
            }

            let data = JSON.parse(rawData); // Parse the JSON string to object

            // Set values to form inputs
            form.find("input[name='edit_id']").val(data.id || "");
            form.find("select[name='platform']").val(data?.platform).trigger("change");
            form.find("select[name='main_menu_id']").val(data?.main_menu_id).trigger("change");
            form.find("input[name='name']").val(data.name || "").focus();
            form.find("input[name='menu_icon']").val(data.menu_icon || "");
            form.find("input[name='route_name']").val(data.route_name || "");
            form.find("input[name='url']").val(data.url || "");
            form.find("input[name='order_by']").val(data.order_by || "");
            form.find("select[name='status']").val(data.status || "active").trigger("change");
            form.find("button[type='submit']").html('Update Menu');
            console.log(!editData, $(this).attr('data-rowMenuData'), $(this).attr('data-rowSubData'));
            // if (!editData && $(this).attr('data-rowSubData')) {
            //     editData = $(this).attr('data-rowSubData');
            // }
            // console.log(formSidebarMenu, editData);
            // if (editData) {
            //     formSidebarMenu.find("input[name='edit_id']").val(editData?.id)
            // }
        });

        $(".menuDelete").on('click', function() {
            let deleteData = JSON.parse($(this).attr('data-rowMenuData'));

            if (deleteData?.id) {
                Swal.fire({
                    title: "Are you sure?",
                    text: "You will not be able to recover this data!",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-danger waves-effect waves-light',
                        cancelButton: "btn btn-primary waves-effect waves-light",
                    },
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "No, cancel please!",
                    showCancelButton: true,
                    reverseButtons: true
                }).then((isConfirmed) => {

                    let formMenu = {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE',
                        delete_id: deleteData?.id,
                        model: "main_menu",
                    }
                    if (deleteData?.main_menu_id) {
                        formMenu = {
                            ...formMenu,
                            model: "sub_menu",
                            main_menu_id: deleteData?.main_menu_id,
                            delete_id: deleteData?.id,
                        }
                    }
                    console.log("menu delete", deleteData, deleteData?.main_menu_id, deleteData?.id,
                        formMenu);

                    if (isConfirmed.isConfirmed) {
                        $.ajax({
                            type: "POST",
                            url: "{{ route('sidebar.menu.delete') }}",
                            data: formMenu,
                            success: function(data) {
                                // Swal.fire("Deleted!", "Category has been deleted.", "success");
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "{{ $page_title }} has been deleted.",
                                    icon: "success",
                                    customClass: {
                                        confirmButton: 'btn btn-primary waves-effect waves-light',
                                    },
                                });
                                location.reload();
                            },
                            error: function() {
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "Something Went wrong deleted failed.",
                                    icon: "error",
                                    customClass: {
                                        confirmButton: 'btn btn-primary waves-effect waves-light',
                                    },
                                });
                            }
                        });
                    } else {
                        Swal.fire({
                            title: "Deleted!",
                            text: "Data is safe :)",
                            icon: "error",
                            customClass: {
                                confirmButton: 'btn btn-primary waves-effect waves-light',
                            },
                        });
                    }
                });
            } else {
                Swal.fire({
                    title: "Wrong!",
                    text: "Something Went wrong, data not found.",
                    icon: "error",
                    customClass: {
                        confirmButton: 'btn btn-primary waves-effect waves-light',
                    },
                });
            }

        });
    </script>
@endpush
