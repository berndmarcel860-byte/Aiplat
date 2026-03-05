// Documents Table Implementation
$(document).ready(function() {
    if ($('#documentsTable').length) {
        // Initialize documents table
        const initDocumentsTable = function() {
            return $('#documentsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "ajax/documents.php",
                    type: "POST",
                    data: function(d) {
                        d.csrf_token = $('meta[name="csrf-token"]').attr('content');
                    },
                    error: function(xhr, error, thrown) {
                        console.error('AJAX Error:', xhr.responseText);
                        $('#documentError').text('Failed to load documents. Please try again.').removeClass('d-none');
                    }
                },
                columns: [
                    { 
                        data: "document_name",
                        render: function(data, type, row) {
                            return data || row.document_type + ' Document';
                        }
                    },
                    { 
                        data: "document_type",
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    { 
                        data: "status",
                        render: function(data, type, row) {
                            return `<span class="badge badge-${row.status_class}">${data}</span>`;
                        }
                    },
                    { 
                        data: "uploaded_at",
                        render: function(data) {
                            return data ? new Date(data).toLocaleString() : '';
                        }
                    },
                    {
                        data: "id",
                        render: function(data, type, row) {
                            return `
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-primary view-document" data-id="${data}" data-path="${row.file_path}">
                                        <i class="anticon anticon-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-document" data-id="${data}">
                                        <i class="anticon anticon-delete"></i> Delete
                                    </button>
                                </div>
                            `;
                        },
                        orderable: false
                    }
                ],
                responsive: true,
                order: [[3, 'desc']],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
                    emptyTable: "No documents found",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    lengthMenu: "Show _MENU_ entries"
                }
            });
        };

        let documentsTable = initDocumentsTable();

        // Refresh button
        $('#refreshDocuments').click(function() {
            $('#documentError').addClass('d-none');
            documentsTable.ajax.reload(null, false);
            toastr.info('Documents refreshed');
        });

        // View document
        $('#documentsTable').on('click', '.view-document', function() {
            const docId = $(this).data('id');
            const filePath = $(this).data('path');
            const $modal = $('#documentPreviewModal');
            const $previewContent = $('#previewContent');
            const $downloadBtn = $('#downloadDocumentBtn');
            const $previewTitle = $('#previewTitle');

            $previewContent.html(`
                <div class="text-center p-4">
                    <i class="anticon anticon-loading anticon-spin" style="font-size:24px"></i>
                    <p>Loading document preview...</p>
                </div>
            `);

            // Set download link
            $downloadBtn.attr('href', 'uploads/' + filePath);
            
            // Determine file type and display accordingly
            const fileExt = filePath.split('.').pop().toLowerCase();
            
            $.get('ajax/get-document.php', { id: docId })
                .done(function(response) {
                    if (response.success) {
                        $previewTitle.text(response.document.document_name || response.document.document_type);
                        
                        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                            $previewContent.html(`
                                <img src="uploads/${filePath}" class="img-fluid" alt="Document Preview" style="max-height: 70vh;">
                                ${response.document.description ? `<div class="mt-3 text-left"><strong>Description:</strong> ${response.document.description}</div>` : ''}
                            `);
                        } else if (fileExt === 'pdf') {
                            $previewContent.html(`
                                <embed src="uploads/${filePath}#toolbar=0&navpanes=0" type="application/pdf" width="100%" height="600px">
                                ${response.document.description ? `<div class="mt-3 text-left"><strong>Description:</strong> ${response.document.description}</div>` : ''}
                            `);
                        } else {
                            $previewContent.html(`
                                <div class="alert alert-info">
                                    <i class="anticon anticon-info-circle"></i> Preview not available for this file type. Please download to view.
                                </div>
                                ${response.document.description ? `<div class="mt-3"><strong>Description:</strong> ${response.document.description}</div>` : ''}
                            `);
                        }
                    } else {
                        $previewContent.html(`
                            <div class="alert alert-danger">
                                ${response.message || 'Failed to load document details'}
                            </div>
                        `);
                    }
                })
                .fail(function(xhr) {
                    $previewContent.html(`
                        <div class="alert alert-danger">
                            ${xhr.status === 401 ? 'Session expired. Please login again.' : 'Failed to load document details. Please try again.'}
                        </div>
                    `);
                });

            $modal.modal('show');
        });

        // Delete document
        $('#documentsTable').on('click', '.delete-document', function() {
            const docId = $(this).data('id');
            
            if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                $.ajax({
                    url: 'ajax/delete-document.php',
                    type: 'POST',
                    data: { id: docId },
                    beforeSend: function() {
                        $(this).prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Deleting...');
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            documentsTable.ajax.reload(null, false);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.status === 401 ? 'Session expired. Please login again.' : 'Failed to delete document.');
                    }
                });
            }
        });

        // File upload form handling
        $('#documentFile').on('change', function() {
            const fileName = $(this).val().split('\').pop();
            $(this).next('.custom-file-label').html(fileName);
            
            // Validate file size (client-side)
            const file = this.files[0];
            if (file && file.size > 10 * 1024 * 1024) { // 10MB limit
                toastr.error('File size exceeds 10MB limit');
                $(this).val('');
                $(this).next('.custom-file-label').html('Choose file (Max 10MB)');
            }
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
                        $('.custom-file-label').html('Choose file (Max 10MB)');
                    } else {
                        toastr.error(response.message);
                    }
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html('<i class="anticon anticon-upload"></i> Upload Document');
                },
                error: function(xhr) {
                    if (xhr.status === 401) {
                        window.location.href = 'login.php';
                    } else if (xhr.responseJSON && xhr.responseJSON.error) {
                        toastr.error(xhr.responseJSON.error);
                    } else {
                        toastr.error('Failed to upload document. Please try again.');
                    }
                }
            });
        });
    }
});