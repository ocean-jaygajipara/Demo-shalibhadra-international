<script>
    $(document).on('click', '.update-status', function() {

        var _token = '{{ csrf_token() }}';
        var _actionUrl = $(this).attr("data-url");
        var _dataId = $(this).attr("data-id");
        var _updateStatus = $(this).attr("data-update_status");
        const modalReload = $(this).attr('modelReload');

        const urlParams = new URLSearchParams(_actionUrl);

        if (!_dataId && urlParams.get('id')) {
            _dataId = urlParams.get('id');
        }
        if (!_updateStatus && urlParams.get('update_status')) {
            _updateStatus = urlParams.get('update_status');
        }
        if (_token && _actionUrl && _dataId && _updateStatus) {
            $.ajax({
                type: "POST",
                url: _actionUrl,
                data: {
                    _token: _token,
                    _method: 'POST',
                    id: _dataId,
                    update_status: _updateStatus,
                },
                success: function(data) {

                    if (data?.status ==true) {
                        toastr.success(data?.message);
                        $("#yajra-datatables").DataTable().ajax.reload();

                        if (modalReload === '#listFollowupModal') {
                            const company_id = $('#listFollowupModal #company_id').val();
                            const inquiry_id = $('#listFollowupModal #inquiry_id').val();
                            const company_name = $('#listFollowupModal #company_name').val();

                            if (company_id && inquiry_id) {
                                loadFollowupList(company_id, inquiry_id, company_name);
                            }
                        }
                    } else {
                        toastr.error(data?.message);
                    }
                },
                error: function() {
                    toastr.error('Something Went wrong deleted failed.');
                }
            });
        } else {
            console.log("Update Script", _token, _actionUrl, urlParams, _dataId, _updateStatus);

        }
    });
</script>
