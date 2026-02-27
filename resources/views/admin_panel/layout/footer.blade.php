<footer>
    <div class="footer-area">
        <p>&copy; Copyright 2025</p>
    </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/metisMenu/3.0.7/metisMenu.min.js"></script>

    <!-- Sparkline -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-sparklines/2.1.2/jquery.sparkline.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-slimScroll/1.3.8/jquery.slimscroll.min.js"></script>
    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" />

    <!-- Owl Carousel JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


    <script src="{{ asset('assets/js/home.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>

    <script>
        $('#sidebar').slimScroll({
            height: '100%'
        });

        $('.owl-carousel').owlCarousel({
            loop: true,
            margin: 10,
            nav: true,
            items: 1
        });
        $(document).ready(function() {
            $('#example').DataTable({
                dom: 'Bfrtip',
                order: [[0, "desc"]],
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    'pdfHtml5',
                    'colvis'
                ]
            });
        });

        // Initialize column visibility if dataTable is already initialized elsewhere or needs specific config
        // Actually the above global init covers #example
        // But for column visibility to work, we need to ensure the button is included.

        function showAlert(title, text, icon) {
            Swal.fire({
                title: title,
                html: text,
                icon: icon,
            });
        }


        function logoutAndDeleteFunction(e) {
            var msg = e.getAttribute("data-msg");
            var method = e.getAttribute("data-method");
            var url = e.getAttribute("data-url");

            swal.fire({
                    title: "Are you sure?",
                    text: msg,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: 'continue',
                    cancelButtonText: 'cancel',
                    dangerMode: true,
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        yourFunction(url, method);
                    } else {
                        swal("Your account is safe!");
                    }
                });

        }

        function yourFunction(url, method) {
            $.ajax({
                url: url,
                type: method,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response['reload'] != undefined) {
                        showAlert("Success", response.success, "success");
                        window.location.reload();
                    }
                    if (response['redirect'] != undefined) {
                        showAlert("Success", response.success, "success");
                        window.location.href = response['redirect'];
                    }
                },
                error: function(xhr, status, error) {
                    // Handle errors
                }
            });
        }

        function multipleerrorshandle(errors) {
            let message = '';
            for (var errorkey in errors) {
                message += "<span style='color:red'>" + errors[errorkey] + "</span><br>";
            }
            showAlert('Errors', message, 'error');
        }

        function ajaxErrorHandling(data, msg) {
            if (data.hasOwnProperty("responseJSON")) {
                var resp = data.responseJSON;
                if (resp.message == 'CSRF token mismatch.') {
                    showAlert("Page has been expired and will reload in 2 seconds", "Page Expired!", "error");
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                    return;
                }
                if (resp.error) {
                    var msg = (resp.error == '') ? 'Something went wrong!' : resp.error;
                    showAlert(msg, "Error!", "error");
                    return;
                }
                if (resp.message != 'The given data was invalid.') {
                    showAlert(resp.message, "Error!", "error");
                    return;
                }
                multipleerrorshandle(resp.errors);
            } else {
                showAlert(msg + "!", "Error!", 'error');
            }
            return;
        }
        //post
        function myAjax(url, formData, method = 'post', callback) {
            $.ajax({
                url: url,
                method: method,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                complete: function(data) {},
                success: function(data) {
                    if (data['reload'] != undefined) {
                        showAlert("Success", data.success, "success");
                        window.location.reload();
                        return false;
                    }
                    if (data['redirect'] != undefined) {
                        showAlert("Success", data.success, "success");
                        window.location.href = data['redirect'];
                        return false;
                    }
                    if (data['error'] !== undefined) {
                        var text = "<span style='color:red'>" + data['error'] + "</span>";
                        showAlert('Error', text, 'error');
                        return false;
                    }
                    if (data['errors'] !== undefined) {
                        multipleerrorshandle(data['errors'])
                        return false;
                    }

                    callback(data)
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    ajaxErrorHandling(jqXHR, errorThrown);
                },

            });
        }
    </script>
