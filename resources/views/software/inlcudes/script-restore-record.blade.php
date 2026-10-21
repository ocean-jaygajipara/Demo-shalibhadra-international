<script>
    $(document).on('click', '.record-restore', function() {
        var _restoreUrl = $(this).attr("data-restore");
        var func = $(this).attr("data-fun");

        Swal.fire({
            title: "Are you sure?",
            text: "Are you sure you want to restore this data?",
            icon: "info",
            customClass: {
                confirmButton: 'btn btn-success waves-effect waves-light',
                cancelButton: "btn btn-primary waves-effect waves-light",
            },
            confirmButtonText: "Yes, restore it!",
            cancelButtonText: "No, cancel please!",
            showCancelButton: true,
            reverseButtons: true
        }).then((isConfirmed) => {
            var _token = '{{ csrf_token() }}';
            if (isConfirmed.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: _restoreUrl,
                    data: {
                        _token: _token,
                        _method: 'POST',
                    },
                    success: function(data) {
                        if (data.status === false && data.message) {
                            // Backend sent status: false (custom fail)
                            toastr.error(data.message);
                        } else {
                            toastr.success(
                                '{{ $page_title1 ?? 'Record' }} has been restored.');

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

                        }
                    },
                    error: function(xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error('Something went wrong, restore failed.');
                        }
                    }
                });
            } else {
                toastr.info('Data is safe :)');
            }
        });
    });
</script>
