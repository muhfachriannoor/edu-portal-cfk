$('.date-picker').mask('0000-00-00');

$('.date-picker').daterangepicker({
    singleDatePicker: true,
    showDropdowns: true,
    autoUpdateInput: false,
    locale: {
        format: 'YYYY-MM-DD',
    }
}).on('apply.daterangepicker', function(ev, picker) {
    $(this).val(picker.startDate.format('YYYY-MM-DD'));
});