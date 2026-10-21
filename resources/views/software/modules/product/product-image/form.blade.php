<!-- Product Image Modal -->
<div class="modal fade" id="product_image_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add Product Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form name="productImageForm" id="productImageForm" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <input type="hidden" name="edit_product_image_id" id="edit_product_image_id" value="">

                        <!-- Image Name -->
                        <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Image Name <span class="text-danger">*</span></label>
                                <input id="image_name" type="text"
                                    class="form-control @error('image_name') is-invalid @enderror" name="image_name"
                                    placeholder="Enter Image Name">
                                @error('image_name')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6 mb-2"></div>

                        <!-- Image Upload -->
                       <div class="col-12 col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Upload Images <span class="text-danger">*</span></label>
                                <input id="image_path" type="file" accept=""
                                    class="form-control @error('image_path') is-invalid @enderror" name="image_path[]" multiple>
                                @error('image_path')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                                <div id="image_path_display">

                                </div>
                            </div>
                        </div>


                    </div>

                    <div class="modal-footer">
                        <button type="button" id="product_image_submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
