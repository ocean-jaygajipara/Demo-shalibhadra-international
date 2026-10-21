<script>
    let productProductMaterialApiRequest = null; // Declare globally, outside the event

    function fetch_ProductMaterialMap() {

        // Abort previous request if any
        if (productProductMaterialApiRequest !== null) {
            productProductMaterialApiRequest.abort();
        }

        let instance =  $('#materialDetailTableBody');

        let company_id = $(".search_by_company option:selected").val();
        let is_select2 = instance.hasClass('select2');
        let product_id = $('#product_id').val();

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (company_id) {
            productProductMaterialApiRequest = $.ajax({
                type: 'POST',
                url: "{{ route('get-product-material-map') }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                    product_id: product_id,
                },
                success: function(response) {
                     if (response.status) {

                        if (response.data && response.data.length > 0) {
                            let tbody = $("#materialDetailTableBody");
                            tbody.empty();

                            let oldMaterials = @json(old('material', $edit->materials ?? []));

                            $.each(response.data, function(i, item) {
                                let oldVal = oldMaterials[i] || {};

                                let sizeFields = '';
                                for (let j = 1; j <= 10; j++) {
                                    sizeFields += `
                                        <td style="min-width: 150px;">
                                            <input type="text" name="material[${i}][size${j}]"
                                                class="form-control"
                                                value="${oldVal['size'+j] ?? ''}"
                                                placeholder="Size ${j}">
                                        </td>
                                    `;
                                }

                                let row = `
                                    <tr>
                                        <td style="min-width: 300px;">
                                            <input type="hidden" name="material[${i}][id]" value="${oldVal.id ?? ''}">
                                            <input type="text" name="material[${i}][dimension]"
                                                class="form-control"
                                                value="${oldVal.dimension ?? item.dimension ?? ''}"
                                                placeholder="Enter Dimension">
                                        </td>
                                        <td style="min-width: 190px;">
                                            <input type="text" name="material[${i}][size]"
                                                class="form-control"
                                                value="${oldVal.size ?? item.size ?? ''}"
                                                placeholder="Enter Size">
                                        </td>
                                        <td style="min-width: 150px;">
                                            <input type="text" name="material[${i}][ltl]"
                                                class="form-control"
                                                value="${oldVal.ltl ?? item.ltl ?? ''}"
                                                placeholder="Enter LTL">
                                        </td>
                                        <td style="min-width: 150px;">
                                            <input type="text" name="material[${i}][utl]"
                                                class="form-control"
                                                value="${oldVal.utl ?? item.utl ?? ''}"
                                                placeholder="Enter UTL">
                                        </td>
                                        <td style="min-width: 250px;">
                                            <input type="text" name="material[${i}][instrument]"
                                                class="form-control"
                                                value="${oldVal.instrument ?? item.instrument ?? ''}"
                                                placeholder="Enter Instrument">
                                        </td>
                                         ${sizeFields}
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger removeRow">X</button>
                                        </td>
                                    </tr>
                                `;
                                tbody.append(row);
                            });
                        }
                    }
                }
            });
        }
    }
</script>
