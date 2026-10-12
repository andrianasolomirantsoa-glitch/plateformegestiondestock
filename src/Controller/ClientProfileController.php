<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/profil')]
#[IsGranted('ROLE_CLIENT')]
class ClientProfileController extends AbstractController
{
    #[Route('', name: 'app_client_profile', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'modifier_profil',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Token CSRF invalide.'
                );
            }

            $nom = trim((string) $request->request->get('nom'));
            $prenom = trim((string) $request->request->get('prenom'));

            if ($nom === '' || $prenom === '') {
                $this->addFlash(
                    'danger',
                    'Le nom et le prénom sont obligatoires.'
                );

                return $this->render(
                    'client/profile/index.html.twig',
                    [
                        'user' => $user,
                    ]
                );
            }

            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre profil a été mis à jour avec succès.'
            );

            return $this->redirectToRoute('app_client_profile');
        }

        return $this->render(
            'client/profile/index.html.twig',
            [
                'user' => $user,
            ]
        );
    }
}