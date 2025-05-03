# Nautic Club Payment System Integration Guide

This document explains how the payment system has been integrated into the Nautic Club application using Stripe as a payment gateway for testing purposes.

## Overview

The payment system allows users to purchase event tickets through a multi-step checkout process:

1. **Step 1**: Payment method selection (Visa, Mastercard, PayPal, or Voucher)
2. **Step 2**: Payment details entry
3. **Step 3**: Order review and confirmation

## Technical Implementation

### Payment Gateway Integration

The application uses the Stripe API for payment processing. The integration follows these principles:

- Separation of concerns: Payment processing logic is separated from the UI
- Security: Card details are collected securely using Stripe Elements
- Testability: Test cards can be used to simulate different payment scenarios

### Key Components

1. **StripePaymentGatewayService**: Handles all payment processing logic
   - Located at: `src/Service/StripePaymentGatewayService.php`
   - Extends the base `PaymentGatewayService` class
   - Communicates with the Stripe API

2. **PaymentController**: Manages the multi-step checkout process
   - Located at: `src/Controller/PaymentController.php`
   - Uses session to maintain state between steps
   - Creates Transaction and Ticket entities

3. **Payment Templates**:
   - Step 1 (`step1.html.twig`): Payment method selection
   - Step 2 (`step2.html.twig`): Payment details collection with Stripe Elements
   - Step 3 (`step3.html.twig`): Order review
   - Success (`success.html.twig`): Payment confirmation

## Configuration

Stripe API keys are configured in `config/services.yaml`:

```yaml
parameters:
    stripe_publishable_key: 'pk_test_51OS4GzBVpxXXXxxxxxxxxxxxxxxxXXXXXXXXXxxxx'
    stripe_secret_key: 'sk_test_51OS4GzBVpxXXXxxxxxxxxxxxxxxxXXXXXXXXXxxxx'
    payment_gateway_url: 'https://api.stripe.com'
    payment_gateway_key: '%stripe_secret_key%'
```

## Using Test Cards

For testing purposes, you can use the following test cards provided by Stripe:

- **Successful payment**: 4242 4242 4242 4242
- **Failed payment**: 4000 0000 0000 0002

For the expiry date, CVV, and cardholder name, you can use any valid values.

## How It Works

1. The user selects a payment method in Step 1
2. In Step 2:
   - For credit cards: The user enters card details in the Stripe Elements form
   - For other methods: The user enters method-specific details
3. The user reviews and confirms the order in Step 3
4. The `StripePaymentGatewayService` processes the payment through Stripe
5. On success:
   - Transaction and Ticket status are updated to "COMPLETED" and "booked"
   - The user is redirected to the success page with transaction details
6. On failure:
   - An error message is displayed
   - The user can retry the payment

## Going to Production

To use this in a production environment:

1. Replace the test Stripe API keys with production keys
2. Ensure PCI compliance by:
   - Never handling card data directly on your server
   - Always using Stripe Elements or Checkout for card collection
3. Implement proper error handling and notifications
4. Set up webhook endpoints to handle asynchronous payment events
5. Consider adding additional payment methods that Stripe supports

## Troubleshooting

If you encounter issues with the payment system:

1. Check the Stripe dashboard for payment logs
2. Verify API keys are correctly configured
3. Inspect browser console for JavaScript errors
4. Check server logs for PHP errors 