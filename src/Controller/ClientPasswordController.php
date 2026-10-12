<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/profil')]
#[IsGranted('ROLE_CLIENT')]
class ClientPasswordController extends AbstractController
{
    #[Route(
        '/mot-de-passe',
        name: 'app_client_password',
        methods: ['GET', 'POST']
    )]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {

            if (!$this->isCsrfTokenValid(
                'modifier_mot_de_passe',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Token CSRF invalide.'
                );
            }

            $ancienMotDePasse =
                (string) $request->request->get('ancien_mot_de_passe');

            $nouveauMotDePasse =
                (string) $request->request->get('nouveau_mot_de_passe');

            $confirmationMotDePasse =
                (string) $request->request->get('confirmation_mot_de_passe');

            /*
             * Vérification de l'ancien mot de passe.
             */
            if (!$passwordHasher->isPasswordValid(
                $user,
                $ancienMotDePasse
            )) {
                $this->addFlash(
                    'danger',
                    'L’ancien mot de passe est incorrect.'
                );

                return $this->render(
                    'client/profile/password.html.twig',
                    [
                        'user' => $user,
                    ]
                );
            }

            /*
             * Vérification de la longueur.
             */
            if (mb_strlen($nouveauMotDePasse) < 8) {
                $this->addFlash(
                    'danger',
                    'Le nouveau mot de passe doit contenir au moins 8 caractères.'
                );

                return $this->render(
                    'client/profile/password.html.twig',
                    [
                        'user' => $user,
                    ]
                );
            }

            /*
             * Vérification de la confirmation.
             */
            if ($nouveauMotDePasse !== $confirmationMotDePasse) {
                $this->addFlash(
                    'danger',
                    'La confirmation du nouveau mot de passe ne correspond pas.'
                );

                return $this->render(
                    'client/profile/password.html.twig',
                    [
                        'user' => $user,
                    ]
                );
            }

            /*
             * Empêcher de reprendre exactement le même mot de passe.
             */
            if ($passwordHasher->isPasswordValid(
                $user,
                $nouveauMotDePasse
            )) {
                $this->addFlash(
                    'warning',
                    'Le nouveau mot de passe doit être différent de l’ancien.'
                );

                return $this->render(
                    'client/profile/password.html.twig',
                    [
                        'user' => $user,
                    ]
                );
            }

            /*
             * Hachage du nouveau mot de passe.
             */
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $nouveauMotDePasse
            );

            $user->setPassword($hashedPassword);
            $user->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre mot de passe a été modifié avec succès.'
            );

            return $this->redirectToRoute(
                'app_client_profile'
            );
        }

        return $this->render(
            'client/profile/password.html.twig',
            [
                'user' => $user,
            ]
        );
    }
}

