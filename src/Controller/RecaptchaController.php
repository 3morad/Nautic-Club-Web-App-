<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecaptchaController extends AbstractController
{
    #[Route('/recaptcha-test', name: 'recaptcha_test')]
    public function index(): Response
    {
        return $this->render('recaptcha_test.html.twig');
    }
} 