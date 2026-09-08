<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($request->isMethod('POST')) {

            $nom = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $passwordConfirmation = $request->request->get('password_confirmation', '');

            // Vérification des champs
            if (
                empty($nom) ||
                empty($prenom) ||
                empty($email) ||
                empty($password)
            ) {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');

                return $this->redirectToRoute('app_register');
            }

            // Vérification du mot de passe
            if ($password !== $passwordConfirmation) {
                $this->addFlash(
                    'error',
                    'Les mots de passe ne correspondent pas.'
                );

                return $this->redirectToRoute('app_register');
            }

            // Vérification de l'email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash(
                    'error',
                    'Veuillez saisir une adresse email valide.'
                );

                return $this->redirectToRoute('app_register');
            }

            // Vérifier si l'utilisateur existe déjà
            $existingUser = $entityManager
                ->getRepository(User::class)
                ->findOneBy(['email' => $email]);

            if ($existingUser) {
                $this->addFlash(
                    'error',
                    'Cette adresse email est déjà utilisée.'
                );

                return $this->redirectToRoute('app_register');
            }

            // Création de l'utilisateur
            $user = new User();

            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);

            // Compte actif
            $user->setActif(true);

            // Hash du mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $password
            );

            $user->setPassword($hashedPassword);

            // Rôle par défaut
            $user->setRoles(['ROLE_USER']);

            // Enregistrement
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre compte a été créé avec succès.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig');
    }
}