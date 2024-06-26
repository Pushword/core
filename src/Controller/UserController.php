<?php

namespace Pushword\Core\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class UserController extends AbstractController
{
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('pushword_admin_dashboard');
        }

        // get the login error if there is one
        $authenticationException = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@Pushword/user/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $authenticationException,
        ]);
    }

    public function logout(): never
    {
        throw new LogicException('This method can be blank');
    }
}
