<script>
    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    }
</script>

@if ($message = Session::get('success'))
    <script>
        toastr.success({!! json_encode($message) !!});
    </script>
@endif

@if ($message = Session::get('error'))
    <script>
        toastr.error({!! json_encode($message) !!});
    </script>
@endif

@if ($message = Session::get('warning'))
    <script>
        toastr.warning({!! json_encode($message) !!});
    </script>
@endif

@if ($message = Session::get('info'))
    <script>
        toastr.info({!! json_encode($message) !!});
    </script>
@endif

@if ($errors->any())
    @foreach ($errors->all() as $error)
        <script>
            toastr.error({!! json_encode($error) !!});
        </script>
    @endforeach
@endif

