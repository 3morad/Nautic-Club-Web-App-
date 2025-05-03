<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Core\Security;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private $urlGenerator;
    private $security;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        Security $security
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // Log access denied message
        error_log('Access denied for user on path: ' . $request->getPathInfo());
        
        // Add flash message
        $request->getSession()->getFlashBag()->add('error', 'You do not have permission to access the requested page.');
        
        // Redirect based on user role
        $user = $this->security->getUser();
        
        if ($user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
            } else {
                return new RedirectResponse($this->urlGenerator->generate('user_dashboard'));
            }
        }
        
        // If no user, redirect to login
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
} 