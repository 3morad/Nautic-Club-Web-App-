<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FormSpreeService
{
    protected $httpClient;
    protected $logger;
    protected $formEndpoint;
    
    public function __construct(
        HttpClientInterface $httpClient = null,
        ParameterBagInterface $params = null,
        LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
        $this->logger = $logger ?? new NullLogger();
        
        // You would get this from your FormSpree account
        // Example: https://formspree.io/f/xleqegkn
        $this->formEndpoint = $params?->get('formspree_endpoint', 'https://formspree.io/f/xleqegkn');
    }
    
    /**
     * Submit feedback to FormSpree
     *
     * @param array $feedbackData
     * @return array Result with success flag and message
     */
    public function submitFeedback(array $feedbackData): array
    {
        try {
            $this->logger->info('Submitting feedback to FormSpree', [
                'feedback_type' => $feedbackData['type'] ?? 'general',
                'has_email' => isset($feedbackData['email']),
            ]);
            
            // For testing purposes, we'll simulate the API call
            if ($this->formEndpoint === 'https://formspree.io/f/xleqegkn') {
                // Simulate success for demo purposes
                $this->logger->info('Using test endpoint, simulating success');
                return $this->simulateFeedbackSubmission($feedbackData);
            }
            
            // Make the actual API call to FormSpree
            $response = $this->httpClient->request('POST', $this->formEndpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $feedbackData,
            ]);
            
            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray(false);
            
            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('Feedback submitted successfully', [
                    'status_code' => $statusCode,
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Thank you for your feedback!',
                    'data' => $responseData
                ];
            } else {
                $this->logger->error('Failed to submit feedback', [
                    'status_code' => $statusCode,
                    'response' => $responseData
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to submit feedback: ' . ($responseData['error'] ?? 'Unknown error'),
                    'data' => $responseData
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Error submitting feedback', [
                'exception' => $e->getMessage(),
                'exception_class' => get_class($e)
            ]);
            
            return [
                'success' => false,
                'message' => 'Error submitting feedback: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Simulate a feedback submission for testing
     */
    private function simulateFeedbackSubmission(array $feedbackData): array
    {
        // Perform validation
        $errors = [];
        
        if (empty($feedbackData['message'])) {
            $errors[] = 'Feedback message is required';
        }
        
        if (isset($feedbackData['email']) && !filter_var($feedbackData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Return errors if validation fails
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ];
        }
        
        // Simulate a 95% success rate
        $success = (mt_rand(1, 100) <= 95);
        
        if ($success) {
            return [
                'success' => true,
                'message' => 'Thank you for your feedback!',
                'data' => [
                    'id' => 'feedback_' . uniqid(),
                    'timestamp' => (new \DateTime())->format('c')
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to submit feedback due to server error',
                'error_code' => 'server_error'
            ];
        }
    }
} 