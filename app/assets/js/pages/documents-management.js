// =============================================
// DOCUMENTS TABLE
// =============================================
$(document).ready(function() {
    if ($('#documentsTable').length) {
        const documentsTable = $('#documentsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/documents.php",
                type: "POST"
            },
            columns: [
                { data: "document_name" },
                { data: "document_type" },
                { 
                    data: "uploaded_at",
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString() : '';
                    }
                },
                {
                    data: "file_path",
                    render: function(data) {
                        return data ? `<a href="uploads/${data}" download class="btn btn-sm btn-primary">
                               <i class="anticon anticon-download"></i> Download
                               </a>` : '';
                    },
                    orderable: false
                }
            ],
            responsive: true,
            order: [[2, 'desc']]
        });

        $('#documentFile').on('change', function() {
            $(this).next('.custom-file-label').html($(this).val().split('\\').pop());
        });

        $('#documentForm').submit(function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const $submitBtn = $(this).find('button[type="submit"]');
            
            $submitBtn.prop('disabled', true)
                .html('<i class="anticon anticon-loading anticon-spin"></i> Uploading...');
            
            $.ajax({
                url: 'ajax/upload-document.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else if (response.success) {
                        toastr.success(response.message);
                        $('#uploadDocumentModal').modal('hide');
                        documentsTable.ajax.reload();
                        $('#documentForm')[0].reset();
                        $('.custom-file-label').html('Choose file');
                    } else {
                        toastr.error(response.message);
                    }
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html('Upload');
                },
                error: function(xhr) {
                    if (xhr.status === 401) {
                        window.location.href = 'login.php';
                    }
                }
            });
        });
    }
});