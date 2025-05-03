<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Manually create an instance of the QrCodeService
    $qrCodeService = new \App\Service\QrCodeService();

    // Test data
    $testData = $qrCodeService->generateTicketValidationData(
        123, // Ticket ID
        'Test Event', // Event name
        new \DateTime(), // Event date
        'General Admission' // Ticket type
    );

    // Generate QR code
    $qrCodeUrl = $qrCodeService->generateQrCodeDataUri($testData);

    // Display the result
    echo '<h1>QR Code Test</h1>';
    echo '<p>Generated Ticket Data: ' . htmlspecialchars($testData) . '</p>';
    echo '<h2>QR Code Image</h2>';
    echo '<img src="' . $qrCodeUrl . '" alt="Test QR Code" />';
} catch (\Exception $e) {
    echo '<h1>Error</h1>';
    echo '<p>Error message: ' . $e->getMessage() . '</p>';
    echo '<p>File: ' . $e->getFile() . ' (Line: ' . $e->getLine() . ')</p>';
    echo '<p>Stack trace:</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
} 