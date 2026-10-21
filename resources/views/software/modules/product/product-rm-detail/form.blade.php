<div class="modal fade" id="product_rm_detail_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add Product RM Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form name="productRmDetailForm" id="productRmDetailForm" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <!-- Hidden field for editing -->
                        <input type="hidden" name="edit_product_rm_detail_id" id="edit_product_rm_detail_id" value="">

                        <!-- Size Premium -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Size Premium <span class="text-danger">*</span></label>
                                <input id="size_premium" type="text"
                                    class="form-control @error('size_premium') is-invalid @enderror"
                                    name="size_premium" placeholder="Enter Size Premium">
                                @error('size_premium')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Outer Diameter (OD) -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Outer Diameter (OD) <span class="text-danger">*</span></label>
                                <input id="od_mm" type="text"
                                    class="form-control @error('od_mm') is-invalid @enderror"
                                    name="od_mm" placeholder="Enter Outer Diameter (OD)">
                                @error('od_mm')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Inner Diameter (ID) -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Inner Diameter (ID) <span class="text-danger">*</span></label>
                                <input id="id_mm" type="text"
                                    class="form-control @error('id_mm') is-invalid @enderror"
                                    name="id_mm" placeholder="Enter Inner Diameter (ID)">
                                @error('id_mm')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Shape -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Shape</label>
                                <input id="shape" type="text"
                                    class="form-control @error('shape') is-invalid @enderror"
                                    name="shape" placeholder="Enter Shape">
                                @error('shape')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Alloy -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Alloy <span class="text-danger">*</span></label>
                                <select name="alloy" id="alloy"
                                    class="form-control @error('alloy') is-invalid @enderror search_by_product_alloy select2"
                                    data-append="search_by_product_alloy"
                                    data-selectedAlloyId=""
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('alloy', $edit->alloy ?? '') ? '' : 'selected' }}>
                                        Select Alloy
                                    </option>
                                </select>
                                @error('alloy')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror

                            </div>
                        </div>

                        <!-- Density -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Density</label>
                                <input id="density" type="text"
                                    class="form-control @error('density') is-invalid @enderror"
                                    name="density" placeholder="Enter Density">
                                @error('density')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Tolerance + -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Tol +</label>
                                <input id="tol_plus" type="text"
                                    class="form-control @error('tol_plus') is-invalid @enderror"
                                    name="tol_plus" placeholder="Enter Tolerance +">
                                @error('tol_plus')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Tolerance - -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Tol -</label>
                                <input id="tol_minus" type="text"
                                    class="form-control @error('tol_minus') is-invalid @enderror"
                                    name="tol_minus" placeholder="Enter Tolerance -">
                                @error('tol_minus')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>


                        <div class="modal-footer mt-3">
                            <button type="button" id="product_rm_detail_submit" class="btn btn-primary">Submit</button>
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
