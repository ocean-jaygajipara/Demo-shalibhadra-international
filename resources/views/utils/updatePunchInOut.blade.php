<script>
$(document).on('click', '#punch_in_out_btn', function() {

    var punch_type = $(this).attr("data-punch-type");
    var punch_text = $(this).attr("data-punch-text");

    var locationData = $('#navigator_location_info').attr("data-navigator-location-info");
    var parsedLocation = locationData ? JSON.parse(locationData) : {};
    company_id = $('meta[name="company_id"]').attr('value');
    var _token = '{{ csrf_token() }}';

    let data = {
        _token: _token,
        _method: 'POST',
        company_id: company_id || null,
        punch_type: punch_type,
        punch_text:punch_text,
    };

    if (punch_type === 'in') {
        data.punch_in_time = formatDateTime(new Date());
        data.punch_in_latitude = parsedLocation.latitude;
        data.punch_in_longitude = parsedLocation.longitude;
       // data.punch_in_address = punch_address || '';
    } else {
        data.punch_out_time = formatDateTime(new Date());
        data.punch_out_latitude = parsedLocation.latitude;
        data.punch_out_longitude = parsedLocation.longitude;
       // data.punch_out_address = punch_address || '';
    }
    if(parsedLocation.latitude != '' && parsedLocation.longitude){
        Swal.fire({
            title: "Are you sure?",
            text: "You are about to record a "+punch_text+" action.",
            icon: "warning",
            customClass: {
                confirmButton: 'btn btn-success waves-effect waves-light',
                cancelButton: "btn btn-secondary waves-effect waves-light",
            },
            confirmButtonText: "Yes, continue!",
            cancelButtonText: "No, cancel!",
            showCancelButton: true,
            reverseButtons: true
        }).then((isConfirmed) => {
            if (isConfirmed.isConfirmed) {

                $.ajax({
                    type: "POST",
                    url: '{{ route("team-attendance.store") }}',
                    data: data,
                    success: function(data) {
                        op_punch_type = punch_type == 'in' ? 'out' : 'in';
                        op_punch_type_txt = punch_type == 'in' ? 'Punch Out' : 'Punch In';
                        $("#punch_in_out_btn").attr("data-punch-type",op_punch_type).attr("data-punch-text",op_punch_type_txt);
                        $(".punch_txt_dv").text(op_punch_type_txt);
                        if(punch_type == 'in'){
                            $(".punch_in_time_dis").text("Punch In Time :" +formatToDMYAMPM(new Date()));
                            $(".punch_in_time_dis").show();
                        }else{
                            $(".punch_in_time_dis").hide();
                        }

                        Swal.fire({
                            title: punch_text+ "!",
                            text: punch_text + " updated Successfully",
                            icon: "success",
                            customClass: {
                                confirmButton: 'btn btn-primary waves-effect waves-light',
                            },
                        });
                    },
                    error: function(xhr) {
                        let errorMsg = "Something went wrong. Punch action failed.";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            title: "Error!",
                            text: errorMsg,
                            icon: "error",
                            customClass: {
                                confirmButton: 'btn btn-primary waves-effect waves-light',
                            },
                        });
                    }
                });
            } else {
                Swal.fire({
                    title: "Cancelled!",
                    text: "Punch action has been cancelled.",
                    icon: "info",
                    customClass: {
                        confirmButton: 'btn btn-primary waves-effect waves-light',
                    },
                });
            }
        });
    }
});
</script>
