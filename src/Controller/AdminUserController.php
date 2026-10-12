<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route(
        '',
        name: 'app_admin_user_index',
        methods: ['GET']
    )]
    public function index(
        UserRepository $userRepository
    ): Response {
        $users = $userRepository->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        $admins = [];

        foreach ($users as $user) {
            if ($user->hasRole('ROLE_ADMIN')) {
                $admins[] = $user;
            }
        }

        return $this->render(
            'admin/user/index.html.twig',
            [
                'admins' => $admins,
            ]
        );
    }

    #[Route(
        '/nouveau',
        name: 'app_admin_user_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($request->isMethod('POST')) {

            if (!$this->isCsrfTokenValid(
                'ajouter_admin',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Token CSRF invalide.'
                );
            }

            $nom = trim(
                (string) $request->request->get('nom')
            );

            $prenom = trim(
                (string) $request->request->get('prenom')
            );

            $email = trim(
                (string) $request->request->get('email')
            );

            $password = (string) $request->request->get('password');

            if (
                $nom === '' ||
                $prenom === '' ||
                $email === '' ||
                $password === ''
            ) {
                $this->addFlash(
                    'danger',
                    'Veuillez remplir tous les champs obligatoires.'
                );

                return $this->render(
                    'admin/user/new.html.twig'
                );
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash(
                    'danger',
                    'L’adresse e-mail n’est pas valide.'
                );

                return $this->render(
                    'admin/user/new.html.twig'
                );
            }

            if (
                mb_strlen($nom) > 100 ||
                mb_strlen($prenom) > 100
            ) {
                $this->addFlash(
                    'danger',
                    'Le nom ou le prénom est trop long.'
                );

                return $this->render(
                    'admin/user/new.html.twig'
                );
            }

            if (mb_strlen($password) < 8) {
                $this->addFlash(
                    'danger',
                    'Le mot de passe doit contenir au moins 8 caractères.'
                );

                return $this->render(
                    'admin/user/new.html.twig'
                );
            }

            $existingUser = $userRepository->findOneBy([
                'email' => $email,
            ]);

            if ($existingUser !== null) {
                $this->addFlash(
                    'danger',
                    'Cette adresse e-mail est déjà utilisée.'
                );

                return $this->render(
                    'admin/user/new.html.twig'
                );
            }

            $user = new User();

            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setRoles([
                'ROLE_ADMIN',
            ]);

            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $password
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le nouvel administrateur a été ajouté avec succès.'
            );

            return $this->redirectToRoute(
                'app_admin_user_index'
            );
        }

        return $this->render(
            'admin/user/new.html.twig'
        );
    }

    #[Route(
        '/{id}/modifier',
        name: 'app_admin_user_edit',
        methods: ['GET', 'POST']
    )]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        /*
         * Vérifier que le compte ciblé est bien un administrateur.
         */
        if (!$user->hasRole('ROLE_ADMIN')) {
            throw $this->createNotFoundException(
                'Administrateur introuvable.'
            );
        }

        if ($request->isMethod('POST')) {

            if (!$this->isCsrfTokenValid(
                'modifier_admin_' . $user->getId(),
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Token CSRF invalide.'
                );
            }

            $nom = trim(
                (string) $request->request->get('nom')
            );

            $prenom = trim(
                (string) $request->request->get('prenom')
            );

            $email = trim(
                (string) $request->request->get('email')
            );

            if (
                $nom === '' ||
                $prenom === '' ||
                $email === ''
            ) {
                $this->addFlash(
                    'danger',
                    'Le nom, le prénom et l’e-mail sont obligatoires.'
                );

                return $this->render(
                    'admin/user/edit.html.twig',
                    [
                        'admin' => $user,
                    ]
                );
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash(
                    'danger',
                    'L’adresse e-mail n’est pas valide.'
                );

                return $this->render(
                    'admin/user/edit.html.twig',
                    [
                        'admin' => $user,
                    ]
                );
            }

            $existingUser = $userRepository->findOneBy([
                'email' => $email,
            ]);

            if (
                $existingUser !== null &&
                $existingUser->getId() !== $user->getId()
            ) {
                $this->addFlash(
                    'danger',
                    'Cette adresse e-mail est déjà utilisée.'
                );

                return $this->render(
                    'admin/user/edit.html.twig',
                    [
                        'admin' => $user,
                    ]
                );
            }

            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setUpdatedAt(
                new \DateTimeImmutable()
            );

            $entityManager->flush();

            $this->addFlash(
                'success',
                'L’administrateur a été modifié avec succès.'
            );

            return $this->redirectToRoute(
                'app_admin_user_index'
            );
        }

        return $this->render(
            'admin/user/edit.html.twig',
            [
                'admin' => $user,
            ]
        );
    }

    #[Route(
        '/{id}/statut',
        name: 'app_admin_user_toggle',
        methods: ['POST']
    )]
    public function toggleStatus(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$user->hasRole('ROLE_ADMIN')) {
            throw $this->createNotFoundException(
                'Administrateur introuvable.'
            );
        }

        if (!$this->isCsrfTokenValid(
            'statut_admin_' . $user->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $currentUser = $this->getUser();

        /*
         * Empêcher l'administrateur de désactiver son propre compte.
         */
        if (
            $currentUser instanceof User &&
            $currentUser->getId() === $user->getId()
        ) {
            $this->addFlash(
                'warning',
                'Vous ne pouvez pas désactiver votre propre compte.'
            );

            return $this->redirectToRoute(
                'app_admin_user_index'
            );
        }

        $user->setActif(!$user->isActif());

        $user->setUpdatedAt(
            new \DateTimeImmutable()
        );

        $entityManager->flush();

        if ($user->isActif()) {
            $this->addFlash(
                'success',
                'L’administrateur a été activé.'
            );
        } else {
            $this->addFlash(
                'warning',
                'L’administrateur a été désactivé.'
            );
        }

        return $this->redirectToRoute(
            'app_admin_user_index'
        );
    }
}

