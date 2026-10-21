<script>
    $(document).on('click', '.record-clone', function() {
        var _cloneUrl = $(this).attr("data-clone");
        var data_type = $(this).attr("data-type");
        var data_id = $(this).attr("data-id");


        Swal.fire({
            title: "Are you sure?",
            text: "Are you sure you want to "+data_type+" this data?",
            icon: "info",
            customClass: {
                confirmButton: 'btn btn-success waves-effect waves-light',
                cancelButton: "btn btn-primary waves-effect waves-light",
            },
            confirmButtonText: "Yes, "+data_type+" it!",
            cancelButtonText: "No, cancel please!",
            showCancelButton: true,
            reverseButtons: true
        }).then((isConfirmed) => {
            var _token = '{{ csrf_token() }}';
            if (isConfirmed.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: _cloneUrl,
                    data: {
                        _token: _token,
                        _method: 'POST',
                        id: data_id,
                        type: data_type,
                    },
                    success: function(data) {
                        if(data.status) {
                            toastr.success(data.message);
                            $("#yajra-datatables").DataTable().ajax.reload();
                           let editUrl = "{{ route($route . '.edit', ':id') }}";
                           editUrl = editUrl.replace(':id', data.quotation_id);
                           window.open(editUrl, '_blank');

                        } else {
                            toastr.error('clone failed.');
                        }
                    },
                    error: function() {
                        toastr.error('Something Went wrong deleted failed.');
                    }
                });
            } else {
                toastr.info('Data is safe :)');
            }
        });
    });
</script>
