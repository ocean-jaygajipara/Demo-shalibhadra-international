<script>
    $(document).ready(function() {
        // console.log("search_by_company L-3");

        let instance = $('.search_by_company');
        let company_id = instance.val();
        let is_required = instance.attr('required');
        let is_select2 = instance.hasClass('select2');

        if (!company_id) {
            company_id = instance.attr("data-selectedCompanyId");
            console.log('ln-12', company_id);
        }

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}get-companies',
            beforeSend: function(request) {
                request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            success: function(response) {
                if (response.status) {
                    let options = "<option value=''>Select Company</option>";
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            if (company_id == item.id) {
                                options += "<option value='" + item.id + "' selected>" + item.company_name + "</option>";
                            } else {
                                options += "<option value='" + item.id + "'>" + item.company_name + "</option>";
                            }
                        });
                    }

                    if (instance.attr('data-append')) {
                        if ($(document).find("." + instance.attr('data-append')).length > 0) {
                            $("." + instance.attr('data-append')).empty();
                            $("." + instance.attr('data-append')).append(options);
                        } else {
                            $("." + instance.attr('data-append')).empty();
                            $("." + instance.attr('data-append')).append(options);
                        }
                    } else if (instance.parent().parent().hasClass("col-md-6") || instance.parent().parent().hasClass("col-md-4") || instance.parent().parent().hasClass("col-md-3")) {
                        let select_tag = '<select name="company_id" class="form-select search_by_company';
                        if (is_select2) {
                            select_tag += ' select2 ';
                        }
                        select_tag += '"';
                        if (is_required) {
                            select_tag += ' required ';
                        }
                        select_tag += '>' + options + '</select>';

                        let html = '';
                        if (instance.parent().parent().hasClass("col-md-4")) {
                            html = '<div class="col-md-4 company_div">';
                        } else if (instance.parent().parent().hasClass("col-md-3")) {
                            html = '<div class="col-md-3 company_div">';
                        } else {
                            html = '<div class="col-md-6 company_div">';
                        }
                        html += '<div class="form-group"><label for="company_id">Select Company ';
                        if (is_required) {
                            html += '<span class="text-danger">*</span>';
                        }
                        html += '</label>';
                        html += select_tag;
                        html += '</div></div>';

                        $(".company_div").remove();
                        instance.parent().parent().after(html);
                    } else if (instance.parent("tr")) {
                        $(".search_by_company").removeClass('d-none').empty().append(options);
                    } else {
                        instance.parent().after(options);
                    }
                    filter_company = $('select[name="company_id"] option:selected').val();
                    if ($.trim(filter_company || '') !== '') {
                        $('.search_by_company').trigger("change");
                    }

                    $('.select2').select2();
                }
            }
        });
    });

    $('.search_by_company').trigger("change");
</script>