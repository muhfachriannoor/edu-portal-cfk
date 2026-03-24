$(document).on('click', '.delete-item', function(ev){
    ev.preventDefault();
    const href = $(this).data("href") || $(this).attr("href");
    const page = ucFirst($('#pageWrapper').data('page')) || 'Item';

    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#4b7cf3",
        cancelButtonColor: "#dc3545",
        confirmButtonText: "Yes, delete it!",
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: function () {
            return new Promise(function (resolve) {
                $.post(href, { _method: "DELETE", _token: $('meta[name="csrf-token"]').attr('content') })
                    .done(function () {
                        dataTable.ajax.reload();
                        swal.fire(
                            "Deleted!",
                            page + " has been deleted.",
                            "success"
                        );
                    })
                    .fail(function (xhr) {
                        var message = xhr.statusText;
                        if (
                            xhr.responseJSON &&
                            xhr.responseJSON.hasOwnProperty("message")
                        ) {
                            message = xhr.responseJSON.message;
                        }

                        swal.fire("Oops...", message, "error");
                    });
            });
        },
    });
});

function ucFirst(str) {
    return str
        .replace(/_/g, ' ')         // Replace underscores with spaces
        .replace(/\b\w/g, c => c.toUpperCase()); // Capitalize first letter of each word;
}