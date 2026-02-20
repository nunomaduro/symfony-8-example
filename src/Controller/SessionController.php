<?php

declare(strict_types=1);

namespace App\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SessionController extends AbstractController
{
    #[Route('/login', name: 'session_create', methods: ['GET'])]
    public function create(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('session/create.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/login', name: 'session_store', methods: ['POST'])]
    public function store(): never
    {
        throw new LogicException('This should never be reached.');
    }

    #[Route('/logout', name: 'session_destroy', methods: ['POST'])]
    public function destroy(): never
    {
        throw new LogicException('This should never be reached.');
    }
}
