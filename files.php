<?php

require_once './configs/SecurityHeadersFiles.php';

$DIR = __DIR__;

// Get vendor path from environment variable, default to './vendor/' if not set
$vendorPath = getenv('VENDOR_PATH') ?: './vendor/';
// Ensure trailing slash
$vendorPath = rtrim($vendorPath, '/') . '/';

// Handle both absolute and relative paths
if (substr($vendorPath, 0, 1) === '/') {
    // Absolute path (Docker container)
    $dibFilesPath = $vendorPath . 'dropinbase/dropinbase/files.php';
} else {
    // Relative path (local development)
    $dibFilesPath = $vendorPath . 'dropinbase/dropinbase/files.php';
}

if(file_exists($dibFilesPath)) {
    include_once($dibFilesPath);
} else {
    header("HTTP/1.0 404 Not Found");
    die("File handler not found at: " . $dibFilesPath);
}