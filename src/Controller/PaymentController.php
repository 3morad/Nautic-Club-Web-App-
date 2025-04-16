<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Security;

class PaymentController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/payment', name: 'payment_page')]
    public function index(): Response
    {
        return $this->render('payment/index.html.twig', [
            'ticket_types' => [
                [
                    'name' => 'Regular Ticket',
                    'price' => 49,
                    'description' => 'Standard entry access'
                ],
                [
                    'name' => 'Exclusive Ticket',
                    'price' => 149,
                    'description' => 'VIP seating + Complimentary drinks'
                ],
                [
                    'name' => 'VIP Experience',
                    'price' => 299,
                    'description' => 'All access pass + Private lounge'
                ],
            ]
        ]);
    }

    #[Route('/payment/process', name: 'payment_process', methods: ['POST'])]
    public function processPayment(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            // Get form data
            $content = $request->getContent();
            $data = json_decode($content, true);
            
            if (!$data) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid JSON data: ' . $content
                ], 400);
            }
            
            // Basic validation
            if (!isset($data['ticketType']) || !isset($data['amount'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Missing required ticket information'
                ], 400);
            }
            
            $paymentMethod = isset($data['paymentMethod']) ? $data['paymentMethod'] : 'CREDIT_CARD';
            
            // Get the user - for demo purposes, we'll create a dummy user if not authenticated
            $user = $this->getUser();
            if (!$user) {
                // Find the first user as a fallback for demo
                $user = $entityManager->getRepository(User::class)->findOneBy([], ['id' => 'ASC']);
                
                if (!$user) {
                    // Create a dummy user for demo purposes
                    $user = new User();
                    $user->setEmail('demo@example.com');
                    $user->setRoles(['ROLE_USER']);
                    $user->setPassword('dummy_password');
                    $entityManager->persist($user);
                    $entityManager->flush();
                }
            }
            
            // Create new transaction
            $transaction = new Transaction();
            $transaction->setType('EVENT_TICKET');
            $transaction->setAmount((float)$data['amount']);
            $transaction->setStatus('PENDING');
            $transaction->setPaymentMethod($paymentMethod);
            $transaction->setUser($user);
            
            // Set payment method specific data
            if ($paymentMethod === 'CREDIT_CARD' || $paymentMethod === 'DEBIT_CARD') {
                if (isset($data['cardNumber'])) {
                    $cardNumber = preg_replace('/\D/', '', $data['cardNumber']);
                    $transaction->setCardLastFour(substr($cardNumber, -4));
                }
            }
            
            // Create ticket with event information
            $ticket = new Ticket();
            $ticket->setTicketType($data['ticketType']);
            $ticket->setPrice((float)$data['amount']);
            $ticket->setTitle($data['ticketName'] ?? 'Event Ticket');
            $ticket->setEventName($data['ticketName'] ?? 'Nautic Club Event');
            $ticket->setEventDate(new \DateTime('+2 weeks')); // Default event date
            
            // Set defaults for required fields
            $ticket->setCategory('event');
            $ticket->setStatus('booked');
            $ticket->setPriority('medium');
            $ticket->setDescription('Purchased online');
            
            // Connect ticket and transaction
            $ticket->setTransaction($transaction);
            $ticket->setCreatedAt(new \DateTimeImmutable());
            $ticket->setCreatedBy($user);
            
            // For this demo, we'll simulate a successful payment
            $transaction->setStatus('COMPLETED');
            
            // Save to database
            $entityManager->persist($transaction);
            $entityManager->persist($ticket);
            $entityManager->flush();
            
            // Generate QR code 
            $qrCode = $this->generateQRCode($transaction->getId());
            $transaction->setQrCode($qrCode);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'transactionId' => $transaction->getId(),
                'qrCode' => $qrCode,
                'ticketInfo' => [
                    'type' => $ticket->getTicketType(),
                    'eventName' => $ticket->getEventName(),
                    'eventDate' => $ticket->getEventDate()->format('Y-m-d H:i'),
                    'price' => $ticket->getPrice()
                ]
            ]);
            
        } catch (\Exception $e) {
            // Log the exception
            error_log('Payment processing error: ' . $e->getMessage());
            
            return $this->json([
                'success' => false,
                'error' => 'Payment processing error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/payment/success/{transactionId}', name: 'payment_success')]
    public function paymentSuccess(int $transactionId, EntityManagerInterface $entityManager): Response
    {
        $transaction = $entityManager->getRepository(Transaction::class)->find($transactionId);
        
        if (!$transaction) {
            throw $this->createNotFoundException('Transaction not found');
        }
        
        // Get the ticket associated with this transaction
        $ticket = $entityManager->getRepository(Ticket::class)->findOneBy(['transaction' => $transaction]);
        
        return $this->render('payment/success.html.twig', [
            'transaction' => $transaction,
            'ticket' => $ticket
        ]);
    }

    private function generateQRCode(int $transactionId): string
    {
        // Implement QR code generation here
        // For now, return a placeholder
        return 'QR_' . $transactionId . '_' . uniqid();
    }
} 