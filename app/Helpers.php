<?php
function formatCurrency($amount, $currency = 'USD') {
    return number_format($amount, 2) . ' ' . $currency;
}

function getTransactionStatusBadge($status) {
    $statusClasses = [
        'pending' => 'warning',
        'completed' => 'success',
        'failed' => 'danger',
        'cancelled' => 'secondary',
        'processing' => 'info'
    ];
    
    $class = $statusClasses[strtolower($status)] ?? 'info';
    return '<span class="badge badge-' . $class . '">' . ucfirst($status) . '</span>';
}

function getTransactionTypeIcon($type) {
    $icons = [
        'deposit' => 'arrow-down text-success',
        'withdrawal' => 'arrow-up text-danger',
        'refund' => 'undo text-primary',
        'fee' => 'file-invoice-dollar text-warning',
        'transfer' => 'exchange-alt text-info'
    ];
    
    return isset($icons[$type]) ? $icons[$type] : 'exchange-alt';
}

function validateFileUpload($file, $allowedTypes, $maxSize) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }
    
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds maximum allowed size of ' . ($maxSize / 1024 / 1024) . 'MB');
    }
    
    return true;
}