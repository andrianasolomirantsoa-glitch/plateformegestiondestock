<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/profil')]
#[IsGranted('ROLE_CLIENT')]
class ClientProfileController extends AbstractController
{
    #[Route('', name: 'app_client_profile', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        KernelInterface $kernel
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

                return $this->redirectToRoute('app_client_profile');
            }

            /** @var UploadedFile|null $photo */
            $photo = $request->files->get('photo');

            $ancienPhoto = $user->getPhotoProfil();
            $nouveauNomPhoto = null;

            if ($photo !== null) {
                if (!$photo->isValid()) {
                    $this->addFlash(
                        'danger',
                        'Le fichier envoyé est invalide.'
                    );

                    return $this->redirectToRoute('app_client_profile');
                }

                // Limite : 2 Mo
                if ($photo->getSize() > 2 * 1024 * 1024) {
                    $this->addFlash(
                        'danger',
                        'La photo ne doit pas dépasser 2 Mo.'
                    );

                    return $this->redirectToRoute('app_client_profile');
                }

                // Vérification du véritable type MIME du fichier
                $mimeType = $photo->getMimeType();

                $extensionsAutorisees = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];

                if (!isset($extensionsAutorisees[$mimeType])) {
                    $this->addFlash(
                        'danger',
                        'Format non autorisé. Utilisez JPG, PNG ou WebP.'
                    );

                    return $this->redirectToRoute('app_client_profile');
                }

                $uploadDirectory = $kernel->getProjectDir()
                    . '/public/uploads/profils';

                if (
                    !is_dir($uploadDirectory)
                    && !mkdir($uploadDirectory, 0775, true)
                    && !is_dir($uploadDirectory)
                ) {
                    throw new \RuntimeException(
                        'Impossible de créer le dossier des photos.'
                    );
                }

                $nouveauNomPhoto = bin2hex(random_bytes(16))
                    . '.'
                    . $extensionsAutorisees[$mimeType];

                try {
                    $photo->move(
                        $uploadDirectory,
                        $nouveauNomPhoto
                    );
                } catch (FileException $exception) {
                    $this->addFlash(
                        'danger',
                        'Impossible d’enregistrer la photo. Réessayez.'
                    );

                    return $this->redirectToRoute('app_client_profile');
                }

                $user->setPhotoProfil($nouveauNomPhoto);
            }

            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            // Supprimer l'ancienne photo après l'enregistrement
            if (
                $nouveauNomPhoto !== null
                && $ancienPhoto !== null
            ) {
                $ancienChemin = $kernel->getProjectDir()
                    . '/public/uploads/profils/'
                    . basename($ancienPhoto);

                if (is_file($ancienChemin)) {
                    unlink($ancienChemin);
                }
            }

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
