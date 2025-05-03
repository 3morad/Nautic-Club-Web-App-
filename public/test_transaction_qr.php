<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create and bootstrap a kernel
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    // Get the container
    $container = $kernel->getContainer();
    
    // Get the QR code service
    $qrCodeService = $container->get('App\Service\QrCodeService');
    
    // Generate a test QR code
    $transactionId = 123;
    $qrCodeValue = 'NAUTIC-EVENT-' . date('Ymd') . '-' . $transactionId;
    $qrCodeUrl = $qrCodeService->generateQrCodeDataUri($qrCodeValue);
    
    // Display the result
    echo '<h1>Transaction QR Code Test</h1>';
    echo '<p>QR Code Value: ' . htmlspecialchars($qrCodeValue) . '</p>';
    echo '<h2>QR Code Image</h2>';
    echo '<img src="' . $qrCodeUrl . '" alt="Test QR Code" style="border: 1px solid #ccc; padding: 10px;" />';
    
    echo '<h2>QR Code URL</h2>';
    echo '<textarea rows="5" cols="100" style="width: 100%;">' . htmlspecialchars($qrCodeUrl) . '</textarea>';
    
} catch (\Exception $e) {
    echo '<h1>Error</h1>';
    echo '<p>Error message: ' . $e->getMessage() . '</p>';
    echo '<p>File: ' . $e->getFile() . ' (Line: ' . $e->getLine() . ')</p>';
    echo '<p>Stack trace:</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
} 