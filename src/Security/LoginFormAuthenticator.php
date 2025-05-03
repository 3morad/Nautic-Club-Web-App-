<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use App\Entity\User;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;
    
    public const LOGIN_ROUTE = 'app_login';
    
    private UrlGeneratorInterface $urlGenerator;
    
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }
    
    public function authenticate(Request $request): Passport
    {
        // Get basic auth data
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');
        
        // Get the reCAPTCHA response
        $recaptchaResponse = $request->request->get('g-recaptcha-response');
        
        // Just check if the reCAPTCHA response exists (minimal check)
        // No actual validation with Google
        if (empty($recaptchaResponse)) {
            throw new \Exception('Please complete the CAPTCHA verification.');
        }
        
        // Skip all the actual validation - just assume it's valid
        // No Google API calls
        
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $username);
        
        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }
    
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        
        if ($user instanceof User && $user->getUserType() === 'admin') {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }
        
        return new RedirectResponse($this->urlGenerator->generate('user_profile'));
    }
    
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}