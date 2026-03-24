$(function () {
    $('form[data-mode="show"]').replaceWith(function () {
        return $(this).contents();
    });
});