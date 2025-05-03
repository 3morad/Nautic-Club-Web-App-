<?php

namespace App\Service;

/**
 * Simple QR Code generation service for ticket validation
 */
class QrCodeService 
{
    /**
     * Generate a QR code data URI for a ticket
     * 
     * @param string $data The data to encode in the QR code
     * @param int $size Size of the QR code in pixels
     * @return string Data URI for the QR code image
     */
    public function generateQrCodeDataUri(string $data, int $size = 200): string
    {
        // Use Google Charts API to generate QR code
        $encodedData = urlencode($data);
        
        // Generate QR code URL
        return "https://chart.googleapis.com/chart?cht=qr&chs={$size}x{$size}&chl={$encodedData}&choe=UTF-8";
    }
    
    /**
     * Generate ticket validation data string
     * 
     * @param int $ticketId Ticket ID
     * @param string $eventName Event name
     * @param \DateTime $eventDate Event date
     * @param string $type Ticket type
     * @return string Encoded ticket data
     */
    public function generateTicketValidationData(int $ticketId, string $eventName, \DateTime $eventDate, string $type): string
    {
        // Create a JSON representation of the ticket data with a simple checksum
        $data = [
            'id' => $ticketId,
            'event' => $eventName,
            'date' => $eventDate->format('Y-m-d H:i:s'),
            'type' => $type,
            'timestamp' => time(),
        ];
        
        // Add a simple checksum to prevent tampering
        $data['checksum'] = $this->generateChecksum($data);
        
        return json_encode($data);
    }
    
    /**
     * Generate a simple checksum for ticket data
     * 
     * @param array $data Ticket data
     * @return string Checksum
     */
    private function generateChecksum(array $data): string
    {
        // Create a string from the data
        $string = $data['id'] . $data['event'] . $data['date'] . $data['type'] . $data['timestamp'];
        
        // Generate a hash
        return hash('sha256', $string);
    }
    
    /**
     * Validate ticket data
     * 
     * @param string $ticketData Encoded ticket data
     * @return array|false Decoded data or false if invalid
     */
    public function validateTicketData(string $ticketData)
    {
        // Decode the data
        $data = json_decode($ticketData, true);
        
        if (!$data) {
            return false;
        }
        
        // Check if all required fields are present
        if (!isset($data['id'], $data['event'], $data['date'], $data['type'], $data['timestamp'], $data['checksum'])) {
            return false;
        }
        
        // Store the provided checksum
        $providedChecksum = $data['checksum'];
        
        // Remove the checksum for verification
        unset($data['checksum']);
        
        // Calculate the expected checksum
        $expectedChecksum = $this->generateChecksum($data);
        
        // Verify the checksum
        if ($providedChecksum !== $expectedChecksum) {
            return false;
        }
        
        return $data;
    }
} 