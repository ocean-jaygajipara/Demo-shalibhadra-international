@if ($message = Session::get('success'))
    <script>
        Swal.fire({
            position: 'top-end',
            // title: '{{ $message }}',
            text: '{{ $message }}',
            timer: 1500,
            buttonsStyling: false,
            showConfirmButton: false,
        });
    </script>
@endif


@if ($message = Session::get('error'))
    <script>
        Swal.fire({
            position: 'top-end',
            // title: '{{ $message }}',
            text: '{{ $message }}',
            timer: 1500,
            buttonsStyling: false,
            showConfirmButton: false,
        });
    </script>
@endif


@if ($message = Session::get('warning'))
    <<script>
        Swal.fire({
            position: 'top-end',
            // title: '{{ $message }}',
            text: '{{ $message }}',
            timer: 1500,
            buttonsStyling: false,
            showConfirmButton: false,
        });
    </script>
@endif


@if ($message = Session::get('info'))
    <script>
        Swal.fire({
            position: 'top-end',
            // title: '{{ $message }}',
            text: '{{ $message }}',
            timer: 1500,
            buttonsStyling: false,
            showConfirmButton: false,
        });
    </script>
@endif


@if ($errors->any())
    @foreach ($errors->all() as $error)
        <script>
            toastr.error('{{ $error }}');
            // Swal.fire({
            //     position: 'top-end',
            //     // title: '{{ $error }}',
            //     text: '{{ $error }}',
            //     timer: 1500,
            //     buttonsStyling: false,
            //     showConfirmButton: false,
            // });
        </script>
    @endforeach
@endif
