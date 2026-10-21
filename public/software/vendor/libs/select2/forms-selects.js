$(function () {
    // Initialize standard selectpicker elements
    var $selectpicker = $(".selectpicker"),
        $select2 = $(".select2"),
        $select2Icons = $(".select2-icons");

    // Helper to render icons inside select2 options
    function formatWithIcon(option) {
        if (!option.id) return option.text;
        var iconClass = $(option.element).data("icon");
        return "<i class='" + iconClass + " me-2'></i>" + option.text;
    }

    // Activate Bootstrap selectpicker if present
    $selectpicker.length && $selectpicker.selectpicker();

    // Activate Select2 on regular selects – always show the search box
    $select2.length && $select2.each(function () {
        var $el = $(this);
        $el.wrap('<div class="position-relative"></div>').select2({
            dropdownParent: $el.parent(),
            minimumResultsForSearch: 0 // always enable search
        });
    });

    // Activate Select2 with icons – always show the search box and render icons
    $select2Icons.length && $select2Icons.wrap('<div class="position-relative"></div>').select2({
        dropdownParent: $select2Icons.parent(),
        templateResult: formatWithIcon,
        templateSelection: formatWithIcon,
        escapeMarkup: function (markup) { return markup; },
        minimumResultsForSearch: 0 // always enable search
    });
});