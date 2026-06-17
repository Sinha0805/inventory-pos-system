$(function () {
    $('[data-confirm]').on('click', function (event) {
        if (!confirm($(this).data('confirm'))) event.preventDefault();
    });
});

