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
use App\Service\StripePaymentGatewayService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\QrCodeService;

class PaymentController extends AbstractController
{
    private $security;
    private $params;
    private $qrCodeService;

    public function __construct(Security $security, ParameterBagInterface $params, QrCodeService $qrCodeService)
    {
        $this->security = $security;
        $this->params = $params;
        $this->qrCodeService = $qrCodeService;
    }

    #[Route('/payment', name: 'payment_page')]
    public function index(Request $request, EntityManagerInterface $entityManager, StripePaymentGatewayService $paymentGateway): Response
    {
        // Get current step from session or default to 1
        $session = $request->getSession();
        $currentStep = $session->get('payment_step', 1);
        $paymentData = $session->get('payment_data', []);
        
        // Handle form submissions based on current step
        if ($request->isMethod('POST')) {
            if ($currentStep == 1) {
                // Process payment method selection
                $paymentMethod = $request->request->get('payment_method');
                if ($paymentMethod) {
                    $paymentData['payment_method'] = $paymentMethod;
                    $session->set('payment_data', $paymentData);
                    $session->set('payment_step', 2);
                    
                    return $this->redirectToRoute('payment_page');
                }
            } elseif ($currentStep == 2) {
                // Process payment details
                $paymentData['details'] = $request->request->all();
                $session->set('payment_data', $paymentData);
                $session->set('payment_step', 3);
                
                return $this->redirectToRoute('payment_page');
            } elseif ($currentStep == 3) {
                // Process final checkout
                $ticketTypes = [
                    'regular-ticket' => ['name' => 'Regular Ticket', 'price' => 49],
                    'exclusive-ticket' => ['name' => 'Exclusive Ticket', 'price' => 149],
                    'vip-experience' => ['name' => 'VIP Experience', 'price' => 299]
                ];
                
                // Ensure we have ticket type data - set default if missing
                if (!isset($paymentData['details']) || !isset($paymentData['details']['ticket_type']) || empty($paymentData['details']['ticket_type'])) {
                    $ticketType = 'regular-ticket'; // Default if missing
                } else {
                    $ticketType = $paymentData['details']['ticket_type'];
                }
                
                $ticketInfo = $ticketTypes[$ticketType] ?? $ticketTypes['regular-ticket'];
                
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
                $transaction->setAmount((float)$ticketInfo['price']);
                $transaction->setStatus('PENDING'); // Set to PENDING until payment is processed
                $transaction->setPaymentMethod(strtoupper($paymentData['payment_method']));
                $transaction->setUser($user);
                
                // Set payment method specific data
                if ($paymentData['payment_method'] === 'visa' || $paymentData['payment_method'] === 'mastercard') {
                    if (isset($paymentData['details']['card_number'])) {
                        $cardNumber = preg_replace('/\D/', '', $paymentData['details']['card_number']);
                        $transaction->setCardLastFour(substr($cardNumber, -4));
                    }
                }
                
                // Create ticket with event information
                $ticket = new Ticket();
                $ticket->setTicketType($ticketInfo['name']);
                $ticket->setPrice((float)$ticketInfo['price']);
                $ticket->setTitle($ticketInfo['name']);
                $ticket->setEventName('Nautic Club ' . $ticketInfo['name'] . ' Event');
                $ticket->setEventDate(new \DateTime('+2 weeks')); // Default event date
                
                // Set defaults for required fields
                $ticket->setCategory('event');
                $ticket->setStatus('pending'); // Set to pending until payment is processed
                $ticket->setPriority('medium');
                $ticket->setDescription('Purchased online through multi-step checkout');
                
                // Connect ticket and transaction
                $ticket->setTransaction($transaction);
                $ticket->setCreatedAt(new \DateTimeImmutable());
                $ticket->setCreatedBy($user);
                
                // Save to database with pending status
                $entityManager->persist($transaction);
                $entityManager->persist($ticket);
                $entityManager->flush();
                
                // Generate QR code 
                $qrCode = $this->generateQRCode($transaction->getId());
                $transaction->setQrCode($qrCode);
                $entityManager->flush();
                
                // Special handling for voucher payments - bypass Stripe
                if ($paymentData['payment_method'] === 'voucher') {
                    // Validate voucher code (in a real app, you would check against a database)
                    $voucherCode = $paymentData['details']['voucher_code'] ?? '';
                    $isValidVoucher = !empty($voucherCode); // Simple validation for demo
                    
                    if ($isValidVoucher) {
                        // Set as completed immediately with voucher reference
                        $transaction->setStatus('COMPLETED');
                        $transaction->setGatewayReference('VOUCHER-' . strtoupper(substr($voucherCode, 0, 8)));
                        $ticket->setStatus('booked');
                        $entityManager->flush();
                        
                        // Clear payment session
                        $session->remove('payment_step');
                        $session->remove('payment_data');
                        
                        // Redirect to success page
                        return $this->redirectToRoute('payment_success', ['transactionId' => $transaction->getId()]);
                    } else {
                        // Invalid voucher
                        $this->addFlash('error', 'Invalid voucher code');
                        return $this->render('payment/step3.html.twig', [
                            'paymentData' => $paymentData,
                            'ticket_types' => $this->getTicketTypes(),
                            'payment_error' => 'Invalid voucher code. Please check and try again.'
                        ]);
                    }
                }
                
                // Process all other payment methods through Stripe gateway
                $paymentResult = $paymentGateway->processPayment($transaction, $paymentData);
                
                if ($paymentResult['success']) {
                    // Update transaction and ticket status to completed
                    $transaction->setStatus('COMPLETED');
                    $ticket->setStatus('booked');
                    $entityManager->flush();
                    
                    // Clear payment session
                    $session->remove('payment_step');
                    $session->remove('payment_data');
                    
                    // Redirect to success page
                    return $this->redirectToRoute('payment_success', ['transactionId' => $transaction->getId()]);
                } else {
                    // Payment failed, show error
                    $this->addFlash('error', 'Payment failed: ' . ($paymentResult['message'] ?? 'Unknown error'));
                    
                    // You could redirect to an error page, or back to the payment form
                    // For now, we'll just keep them on step 3 to retry
                    return $this->render('payment/step3.html.twig', [
                        'paymentData' => $paymentData,
                        'ticket_types' => $this->getTicketTypes(),
                        'payment_error' => $paymentResult['message'] ?? 'Payment processing failed'
                    ]);
                }
            }
        }
        
        // Pass Stripe publishable key to templates
        $stripePublishableKey = $this->params->get('stripe_publishable_key');
        
        // Render the appropriate step template
        return $this->render('payment/step' . $currentStep . '.html.twig', [
            'paymentData' => $paymentData,
            'stripe_publishable_key' => $stripePublishableKey,
            'ticket_types' => $this->getTicketTypes()
        ]);
    }

    #[Route('/payment/success/{transactionId}', name: 'payment_success')]
    public function success(int $transactionId, EntityManagerInterface $entityManager): Response
    {
        // Get the transaction
        $transaction = $entityManager->getRepository(Transaction::class)->find($transactionId);
        
        // If transaction not found with ID, try to find the most recent transaction for the current user
        if (!$transaction && $this->getUser()) {
            $transaction = $entityManager->getRepository(Transaction::class)
                ->findOneBy(['user' => $this->getUser()], ['id' => 'DESC']);
        }
        
        // If still no transaction, create a demo one
        if (!$transaction) {
            // Get or create a user
            $user = $this->getUser();
            if (!$user) {
                $user = $entityManager->getRepository(User::class)->findOneBy([], ['id' => 'ASC']);
            }
            
            // Create a demo transaction
            $transaction = new Transaction();
            $transaction->setType('EVENT_TICKET');
            $transaction->setAmount(49.00);
            $transaction->setStatus('COMPLETED');
            $transaction->setPaymentMethod('VOUCHER');
            $transaction->setUser($user);
            $transaction->setGatewayReference('DEMO-TRANSACTION');
            
            // Generate QR code
            $qrCode = $this->generateQRCode(999);
            $transaction->setQrCode($qrCode);
            
            $entityManager->persist($transaction);
            
            // Create a demo ticket
            $ticket = new Ticket();
            $ticket->setTicketType('Regular Ticket');
            $ticket->setPrice(49.00);
            $ticket->setTitle('Regular Ticket');
            $ticket->setEventName('Nautic Club Regular Ticket Event');
            $ticket->setEventDate(new \DateTime('+2 weeks'));
            $ticket->setCategory('event');
            $ticket->setStatus('booked');
            $ticket->setPriority('medium');
            $ticket->setDescription('Demo ticket');
            $ticket->setTransaction($transaction);
            $ticket->setCreatedAt(new \DateTimeImmutable());
            $ticket->setCreatedBy($user);
            
            $entityManager->persist($ticket);
            $entityManager->flush();
            
            return $this->render('payment/success.html.twig', [
                'transaction' => $transaction,
                'ticket' => $ticket
            ]);
        }
        
        // Get the ticket associated with this transaction
        $ticket = $entityManager->getRepository(Ticket::class)->findOneBy(['transaction' => $transaction]);
        
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found for this transaction');
        }
        
        return $this->render('payment/success.html.twig', [
            'transaction' => $transaction,
            'ticket' => $ticket
        ]);
    }

    #[Route('/payment/back', name: 'payment_back_step')]
    public function backStep(Request $request): Response
    {
        $session = $request->getSession();
        $currentStep = $session->get('payment_step', 1);
        
        // Go back one step
        if ($currentStep > 1) {
            $session->set('payment_step', $currentStep - 1);
        }
        
        return $this->redirectToRoute('payment_page');
    }

    private function generateQRCode(int $transactionId): string
    {
        // Create a simple QR code value
        $qrCodeValue = 'NAUTIC-EVENT-' . date('Ymd') . '-' . $transactionId;
        
        // Generate a QR code image URL using our service
        $qrCodeUrl = $this->qrCodeService->generateQrCodeDataUri($qrCodeValue);
        
        return $qrCodeUrl;
    }
    
    /**
     * Get standard ticket types array for templates
     * 
     * @return array Ticket type information
     */
    private function getTicketTypes(): array
    {
        return [
            [
                'name' => 'Regular Ticket',
                'price' => 49,
                'description' => 'Standard entry access',
                'type' => 'regular-ticket'
            ],
            [
                'name' => 'Exclusive Ticket',
                'price' => 149,
                'description' => 'VIP seating + Complimentary drinks',
                'type' => 'exclusive-ticket'
            ],
            [
                'name' => 'VIP Experience',
                'price' => 299,
                'description' => 'All access pass + Private lounge',
                'type' => 'vip-experience'
            ],
        ];
    }
} 