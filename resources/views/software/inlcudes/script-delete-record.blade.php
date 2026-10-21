<script>
    $(document).on('click', '.deletebutton', function() {
        var uid = $(this).attr("data-id");
        var did = $(this).attr("data-did");
        var func = $(this).attr("data-fun");

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
            var _token = '{{ csrf_token() }}';
            var _url = did;
            if (isConfirmed.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: _url,
                    data: {
                        _token: _token,
                        _method: 'DELETE',
                    },
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


                        if (func) {
                            try {
                                new Function(func)();
                            } catch (e) {
                               alert("elsee 2222")
                                $("#yajra-datatables").DataTable().ajax.reload();
                            }
                        } else {
                            $("#yajra-datatables").DataTable().ajax.reload();
                        }
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
    });
</script>
