<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\ProduitImage;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/produits')]
class ProduitController extends AbstractController
{
    #[Route('', name: 'app_produit_index', methods: ['GET'])]
    public function index(
        ProduitRepository $produitRepository
    ): Response {
        return $this->render('produit/index.html.twig', [
            'produits' => $produitRepository->findBy(
                [],
                ['createdAt' => 'DESC']
            ),
        ]);
    }


    #[Route('/nouveau', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $produit = new Produit();

        $form = $this->createForm(
            ProduitType::class,
            $produit
        );

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            /*
             * =====================================================
             * PHOTO PRINCIPALE
             * =====================================================
             */

            /** @var UploadedFile|null $imageFile */
            $imageFile = $form
                ->get('imageFile')
                ->getData();


            if ($imageFile instanceof UploadedFile) {

                $filename = $this->storeImage(
                    $imageFile,
                    $produit->getReference()
                );

                $produit->setImage($filename);
            }


            /*
             * =====================================================
             * PHOTOS SUPPLÉMENTAIRES
             * =====================================================
             */

            /** @var UploadedFile[] $imageFiles */
            $imageFiles = $form
                ->get('imageFiles')
                ->getData();


            if (is_array($imageFiles)) {

                $ordre = 1;


                foreach ($imageFiles as $uploadedImage) {

                    if (!$uploadedImage instanceof UploadedFile) {
                        continue;
                    }


                    $filename = $this->storeImage(
                        $uploadedImage,
                        $produit->getReference(),
                        'detail'
                    );


                    $produitImage = new ProduitImage();

                    $produitImage
                        ->setFilename($filename)
                        ->setOrdre($ordre);


                    $produit->addImage(
                        $produitImage
                    );


                    $ordre++;
                }
            }


            /*
             * =====================================================
             * ENREGISTREMENT
             * =====================================================
             */

            $entityManager->persist($produit);

            $entityManager->flush();


            $this->addFlash(
                'success',
                'Le produit a été ajouté avec succès.'
            );


            return $this->redirectToRoute(
                'app_produit_index'
            );
        }


        return $this->render(
            'produit/new.html.twig',
            [
                'produit' => $produit,
                'form' => $form,
            ]
        );
    }


    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'])]
    public function show(
        Produit $produit
    ): Response {
        return $this->render(
            'produit/show.html.twig',
            [
                'produit' => $produit,
            ]
        );
    }


    #[Route('/{id}/modifier', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Produit $produit,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(
            ProduitType::class,
            $produit
        );

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            /*
             * =====================================================
             * NOUVELLE PHOTO PRINCIPALE
             * =====================================================
             */

            /** @var UploadedFile|null $imageFile */
            $imageFile = $form
                ->get('imageFile')
                ->getData();


            if ($imageFile instanceof UploadedFile) {

                $uploadDir = $this->getUploadDir();

                $oldImage = $produit->getImage();


                $filename = $this->storeImage(
                    $imageFile,
                    $produit->getReference()
                );


                $produit->setImage($filename);


                /*
                 * Suppression de l'ancienne photo principale
                 */

                if (
                    $oldImage
                    && $oldImage !== $filename
                ) {

                    $oldImagePath =
                        $uploadDir
                        . DIRECTORY_SEPARATOR
                        . $oldImage;


                    if (is_file($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            }


            /*
             * =====================================================
             * AJOUT DE PHOTOS SUPPLÉMENTAIRES
             * =====================================================
             */

            /** @var UploadedFile[] $imageFiles */
            $imageFiles = $form
                ->get('imageFiles')
                ->getData();


            if (is_array($imageFiles)) {

                /*
                 * On continue l'ordre après les images existantes.
                 */

                $ordre = 1;


                foreach ($produit->getImages() as $existingImage) {

                    if ($existingImage->getOrdre() >= $ordre) {

                        $ordre =
                            $existingImage->getOrdre() + 1;
                    }
                }


                foreach ($imageFiles as $uploadedImage) {

                    if (!$uploadedImage instanceof UploadedFile) {
                        continue;
                    }


                    $filename = $this->storeImage(
                        $uploadedImage,
                        $produit->getReference(),
                        'detail'
                    );


                    $produitImage = new ProduitImage();

                    $produitImage
                        ->setFilename($filename)
                        ->setOrdre($ordre);


                    $produit->addImage(
                        $produitImage
                    );


                    $ordre++;
                }
            }


            /*
             * =====================================================
             * ENREGISTREMENT
             * =====================================================
             */

            $entityManager->flush();


            $this->addFlash(
                'success',
                'Le produit a été modifié avec succès.'
            );


            return $this->redirectToRoute(
                'app_produit_index'
            );
        }


        return $this->render(
            'produit/edit.html.twig',
            [
                'produit' => $produit,
                'form' => $form,
            ]
        );
    }


    
#[Route('/{id}/supprimer', name: 'app_produit_delete', methods: ['POST'])]
public function delete(
    Request $request,
    Produit $produit,
    EntityManagerInterface $entityManager
): Response {
    if (!$this->isCsrfTokenValid(
        'delete' . $produit->getId(),
        $request->request->get('_token')
    )) {
        $this->addFlash('danger', 'Jeton de sécurité invalide.');

        return $this->redirectToRoute('app_produit_index');
    }

    if ($produit->getImages()->count() > 0) {
        // Les images seront supprimées uniquement si le produit
        // peut réellement être supprimé de la base.
    }

    // Vérifier si le produit possède des entrées de stock.
    $nombreEntrees = (int) $entityManager->getConnection()
        ->fetchOne(
            'SELECT COUNT(*) FROM entrees_stock WHERE produit_id = ?',
            [$produit->getId()]
        );

    if ($nombreEntrees > 0) {
        // Préserver le produit et son historique.
        $produit->setActif(false);
        $entityManager->flush();

        $this->addFlash(
            'warning',
            'Ce produit possède un historique de stock. Il a été désactivé au lieu d’être supprimé.'
        );

        return $this->redirectToRoute('app_produit_index');
    }

    // Le produit n'a aucune entrée de stock : suppression définitive.
    $uploadDir = $this->getUploadDir();

    $filenames = [];

    if ($produit->getImage()) {
        $filenames[] = $produit->getImage();
    }

    foreach ($produit->getImages() as $image) {
        if ($image->getFilename()) {
            $filenames[] = $image->getFilename();
        }
    }

    $entityManager->remove($produit);
    $entityManager->flush();

    // Ne supprimer les fichiers qu'après la réussite de la suppression SQL.
    foreach (array_unique($filenames) as $filename) {
        $imagePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (is_file($imagePath)) {
            unlink($imagePath);
        }
    }

    $this->addFlash('success', 'Le produit a été supprimé avec succès.');

    return $this->redirectToRoute('app_produit_index');
}


    /*
     * =========================================================
     * DOSSIER DES IMAGES
     * =========================================================
     */

    private function getUploadDir(): string
    {
        $uploadDir =
            $this->getParameter('kernel.project_dir')
            . '/public/images/produits';


        if (!is_dir($uploadDir)) {

            mkdir(
                $uploadDir,
                0775,
                true
            );
        }


        return $uploadDir;
    }


    /*
     * =========================================================
     * ENREGISTREMENT D'UNE IMAGE
     * =========================================================
     */

    private function storeImage(
        UploadedFile $file,
        ?string $reference,
        string $type = 'principal'
    ): string {
        $uploadDir =
            $this->getUploadDir();


        /*
         * Référence utilisée dans le nom du fichier.
         */

        $safeReference =
            $reference ?: 'produit';


        $safeReference =
            preg_replace(
                '/[^A-Za-z0-9_-]/',
                '-',
                $safeReference
            );


        $safeReference =
            trim(
                $safeReference,
                '-'
            );


        if ($safeReference === '') {
            $safeReference = 'produit';
        }


        /*
         * Extension du fichier.
         */

        $extension =
            strtolower(
                $file->guessExtension() ?? ''
            );


        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp',
        ];


        if (
            !in_array(
                $extension,
                $allowedExtensions,
                true
            )
        ) {

            throw new \RuntimeException(
                'Format d’image non autorisé.'
            );
        }


        /*
         * Nom unique.
         *
         * principal :
         * GLACE-001-a83f42d1.jpg
         *
         * détail :
         * GLACE-001-detail-a83f42d1.jpg
         */

        $suffix =
            bin2hex(
                random_bytes(4)
            );


        if ($type === 'detail') {

            $filename =
                $safeReference
                . '-detail-'
                . $suffix
                . '.'
                . $extension;

        } else {

            $filename =
                $safeReference
                . '-'
                . $suffix
                . '.'
                . $extension;
        }


        /*
         * Déplacement physique.
         */

        $file->move(
            $uploadDir,
            $filename
        );


        return $filename;
    }
}

