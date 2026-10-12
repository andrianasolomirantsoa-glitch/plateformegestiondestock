<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token
    ): RedirectResponse {
        $user = $token->getUser();

        if ($user->hasRole('ROLE_ADMIN')) {
            return new RedirectResponse(
                $this->router->generate('app_dashboard')
            );
        }

        if ($user->hasRole('ROLE_CLIENT')) {
            return new RedirectResponse(
                $this->router->generate('app_client_home')
            );
        }

        return new RedirectResponse(
            $this->router->generate('app_login')
        );
    }
}