<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class UserProfileSecuritySubscriber implements EventSubscriberInterface
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only process POST requests that might be form submissions
        if (!$request->isMethod('POST')) {
            return;
        }
        
        // Skip if the user is an admin or not authenticated
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY') || 
            $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        // Check if this might be a profile edit form submission
        $path = $request->getPathInfo();
        $isProfileEdit = (
            strpos($path, '/profile') !== false || 
            strpos($path, '/user') !== false
        );
        
        if ($isProfileEdit) {
            // Remove protected fields to prevent regular users from changing them
            $request->request->remove('status');
            $request->request->remove('user_type');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10], // High priority to run early
        ];
    }
} 