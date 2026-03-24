$(function(){
    $('.dropify').dropify();

    const container = document.querySelector('#sidebar');
    if (container) {
        new PerfectScrollbar(container);
    }
});