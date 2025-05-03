<?php

namespace App\Service;

use App\Entity\Transaction;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PaymentGatewayService
{
    protected $httpClient;
    protected $params;
    protected $logger;
    protected $gatewayUrl;
    protected $apiKey;
    
    public function __construct(
        HttpClientInterface $httpClient = null,
        ParameterBagInterface $params = null,
        LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
        $this->params = $params;
        $this->logger = $logger ?? new NullLogger();
        
        // In a real app, these would come from parameters.yaml
        $this->gatewayUrl = $params ? $params->get('payment_gateway_url', 'https://api.example-payment-gateway.com') : 'https://api.example-payment-gateway.com';
        $this->apiKey = $params ? $params->get('payment_gateway_key', 'sandbox_api_key_123') : 'sandbox_api_key_123';
    }
    
    /**
     * Process a payment through the payment gateway API
     *
     * @param Transaction $transaction
     * @param array $paymentData
     * @return array Payment result with success flag and message
     */
    public function processPayment(Transaction $transaction, array $paymentData): array
    {
        try {
            // Log payment attempt
            $this->logger->info('Processing payment for transaction', [
                'transaction_id' => $transaction->getId(),
                'amount' => $transaction->getAmount(),
                'payment_method' => $transaction->getPaymentMethod()
            ]);
            
            // For a real integration, we would make an API call to a payment gateway
            // This is a mock implementation
            $mockApiResponse = $this->mockPaymentApiCall($transaction, $paymentData);
            
            // Process the response
            if ($mockApiResponse['success']) {
                // Update transaction with gateway reference
                $transaction->setGatewayReference($mockApiResponse['reference_id']);
                
                return [
                    'success' => true,
                    'reference_id' => $mockApiResponse['reference_id'],
                    'message' => 'Payment processed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error_code' => $mockApiResponse['error_code'],
                    'message' => $mockApiResponse['message']
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Payment processing error', [
                'exception' => $e->getMessage(),
                'transaction_id' => $transaction->getId()
            ]);
            
            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * In a real app, this would be replaced with actual API call to a payment gateway
     * This method mocks a payment gateway API call for demo purposes
     */
    private function mockPaymentApiCall(Transaction $transaction, array $paymentData): array
    {
        // In a real integration, you would make an API call like this:
        /*
        $response = $this->httpClient->request('POST', $this->gatewayUrl . '/v1/payments', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'amount' => $transaction->getAmount() * 100, // Convert to cents
                'currency' => 'USD',
                'payment_method' => $this->mapPaymentMethod($transaction->getPaymentMethod()),
                'payment_details' => $this->getPaymentDetails($paymentData),
                'description' => 'Payment for transaction ID: ' . $transaction->getId(),
                'metadata' => [
                    'transaction_id' => $transaction->getId()
                ]
            ]
        ]);
        
        return $response->toArray();
        */
        
        // For demonstration, we'll simulate a successful response
        // In real implementation, there would be validation and proper API calls
        
        // Simulate a 98% success rate
        $shouldSucceed = (mt_rand(1, 100) <= 98);
        
        if ($shouldSucceed) {
            return [
                'success' => true,
                'reference_id' => 'ref_' . uniqid(),
                'transaction_id' => 'txn_' . uniqid(),
                'status' => 'approved',
                'amount' => $transaction->getAmount(),
                'currency' => 'USD',
                'payment_method' => strtolower($transaction->getPaymentMethod()),
            ];
        } else {
            $errorCodes = ['insufficient_funds', 'expired_card', 'card_declined', 'invalid_details'];
            $errorMessages = [
                'insufficient_funds' => 'Insufficient funds in account',
                'expired_card' => 'Card has expired',
                'card_declined' => 'Card was declined by issuer',
                'invalid_details' => 'Invalid payment details provided'
            ];
            
            $errorCode = $errorCodes[array_rand($errorCodes)];
            
            return [
                'success' => false,
                'error_code' => $errorCode,
                'message' => $errorMessages[$errorCode] ?? 'Payment processing failed',
                'transaction_id' => null
            ];
        }
    }
    
    /**
     * Maps internal payment method to gateway payment method codes
     */
    private function mapPaymentMethod(string $paymentMethod): string
    {
        $map = [
            'VISA' => 'card',
            'MASTERCARD' => 'card',
            'PAYPAL' => 'paypal',
            'VOUCHER' => 'gift_card'
        ];
        
        return $map[strtoupper($paymentMethod)] ?? 'unknown';
    }
    
    /**
     * Formats payment details based on payment method
     */
    private function getPaymentDetails(array $paymentData): array
    {
        if (in_array(strtoupper($paymentData['payment_method']), ['VISA', 'MASTERCARD'])) {
            return [
                'card_number' => $paymentData['details']['card_number'] ?? '',
                'expiry_month' => substr($paymentData['details']['expiry'] ?? '', 0, 2),
                'expiry_year' => '20' . substr($paymentData['details']['expiry'] ?? '', 3, 2),
                'cvv' => $paymentData['details']['cvv'] ?? '',
                'cardholder_name' => $paymentData['details']['name'] ?? ''
            ];
        } elseif (strtoupper($paymentData['payment_method']) === 'PAYPAL') {
            return [
                'email' => $paymentData['details']['email'] ?? '',
                // In a real app, you wouldn't pass the password to the payment gateway,
                // instead you would direct to PayPal's login screen
            ];
        } elseif (strtoupper($paymentData['payment_method']) === 'VOUCHER') {
            return [
                'voucher_code' => $paymentData['details']['voucher_code'] ?? '',
            ];
        }
        
        return [];
    }
} 