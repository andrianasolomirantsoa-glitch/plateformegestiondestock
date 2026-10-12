<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActif()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est désactivé. Veuillez contacter l’administrateur.'
            );
        }
    }

    public function checkPostAuth(
        UserInterface $user,
        ?\Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token = null
    ): void {
        // Aucun contrôle supplémentaire après authentification.
    }
}