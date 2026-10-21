 <script>
$(document).ready(function() {

    if ($('meta[name="company_id"]').attr('value')) {
        get_sales_status();
    }

    $(document).on('change', '.search_by_company', function() {
        if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected").val() !== "") {
            get_sales_status();
        }
    });

    $(document).on('change', '#salestype', function() {
        get_sales_status();
    });

    function get_sales_status(){
        let company_id = $('#company_id').val();
        let salestype = $('#salestype').val();

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        let data = {
            company_id: company_id,
            _token: "{{ csrf_token() }}"
        };

        if (salestype) {
            data.salestype = salestype;
        }else{
            data.salestype = '<?= (isset($modules['module_name']) && !empty($modules['module_name'])) ? $modules['module_name'] : '' ;?>';
        }
        $(".status_slug_dropdown li").addClass("hide");
        $('.status_slug_dropdown li[data-status="' + data.salestype + '"]').removeClass("hide");

        if(company_id){
             $.ajax({
                url:  "{{ route('get-status') }}",
                type: "post",
                dataType: "json",
                data: data,
                success: function(response) {
                    if(response.status == true) {
                        if(response.data.length > 0) {
                            $('#parent_status_id').empty();
                            $('#parent_status_id').append('<option value="">Select Status</option>');
                            $.each(response.data, function(key, value) {
                                $('#parent_status_id').append('<option value="'+value.id+'">'+value.statusname+'</option>');
                            });
                            let dbParentStatusId = $('#db_parent_status_id').val();
                            $('#parent_status_id').val(dbParentStatusId).trigger('change');
                        }else{
                            let options = "<option value=''>Select Sales Status</option>";
                             $('#parent_status_id').html(options);
                        }
                    }
                }
            });
        }

    }
    let dbParentStatusId = $('#db_parent_status_id').val();
    if(dbParentStatusId != '') {
        get_sales_status();
    }
});
 </script>
