<div class="modal fade" id="showCustomModel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title" id="modelTitle">{{ isset($modelTitle) ? $modelTitle : '' }}</h5>
                <div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="card-datatable text-nowrap mt-3">
                <div class="modelShowHtml"></div>
            </div>
        </div>
    </div>
</div>
