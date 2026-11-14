// assets/js/script.js
$(document).ready(function() {
    // Initialize DataTables
    $('#casesTable, #completedTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 25
    });
    
    // Auto-calculate difference
    $('#z_report, #cash_sales').on('input', function() {
        const zReport = parseFloat($('#z_report').val()) || 0;
        const cashSales = parseFloat($('#cash_sales').val()) || 0;
        const difference = zReport - cashSales;
        // You can display difference somewhere if needed
    });
    
    // Confirm delete actions
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});

// Utility function for formatting numbers
function formatNumber(num) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = $(`
        <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    $('.toast-container').append(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    setTimeout(() => toast.remove(), 5000);
}

// Comment functionality
function addComment(caseId) {
    const comment = $('#comment-input').val();
    if (!comment.trim()) return;
    
    $.ajax({
        url: 'api.php?action=add_comment',
        method: 'POST',
        data: { case_id: caseId, comment: comment },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                showToast('Failed to add comment', 'error');
            }
        },
        error: function() {
            showToast('An error occurred', 'error');
        }
    });
}

// Export functionality
function exportData(type) {
    window.location.href = `export.php?type=${type}`;
}

// Progress badge color mapping
function getProgressBadgeClass(progress) {
    const map = {
        'Done': 'success',
        'In Progress': 'warning',
        'Not Start': 'secondary',
        'Not Done': 'danger',
        'Escalated': 'danger'
    };
    return map[progress] || 'secondary';
}
// Add to script.js or in the page
function viewAttachment(attachmentId) {
    // Open in new tab/window
    window.open('download.php?file=' + attachmentId, '_blank');
}

function downloadAttachment(attachmentId) {
    // Force download
    window.location.href = 'download.php?file=' + attachmentId + '&download=1';
}

function getAttachmentInfo(attachmentId) {
    fetch('api.php?action=get_attachment_info', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ attachment_id: attachmentId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Display file info
            alert('File: ' + data.file_name + '\nSize: ' + (data.file_size / 1024).toFixed(2) + ' KB');
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}