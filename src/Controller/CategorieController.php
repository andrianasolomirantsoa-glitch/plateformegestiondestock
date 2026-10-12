<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class CategorieController extends AbstractController
{
    /**
     * Liste des catégories
     */
    #[Route('', name: 'app_categorie_index', methods: ['GET'])]
    public function index(
        CategorieRepository $categorieRepository,
        Request $request
    ): Response {
        $search = trim((string) $request->query->get('search', ''));

        $categories = $categorieRepository->findBy(
            [],
            ['nom' => 'ASC']
        );

        if ($search !== '') {
            $categories = array_values(
                array_filter(
                    $categories,
                    static function (Categorie $categorie) use ($search): bool {
                        $nom = mb_strtolower($categorie->getNom() ?? '');
                        $description = mb_strtolower($categorie->getDescription() ?? '');
                        $needle = mb_strtolower($search);

                        return str_contains($nom, $needle)
                            || str_contains($description, $needle);
                    }
                )
            );
        }

        return $this->render('admin/categorie/index.html.twig', [
            'categories' => $categories,
            'search' => $search,
        ]);
    }


    /**
     * Ajouter une catégorie
     */
    #[Route('/nouveau', name: 'app_categorie_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieRepository $categorieRepository
    ): Response {
        $categorie = new Categorie();

        if ($request->isMethod('POST')) {

            if (!$this->isCsrfTokenValid(
                'creer_categorie',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Jeton CSRF invalide.'
                );
            }

            $nom = trim((string) $request->request->get('nom'));
            $description = trim((string) $request->request->get('description'));

            if ($nom === '') {
                $this->addFlash(
                    'danger',
                    'Le nom de la catégorie est obligatoire.'
                );

                return $this->render('admin/categorie/new.html.twig', [
                    'categorie' => $categorie,
                ]);
            }

            if (mb_strlen($nom) > 100) {
                $this->addFlash(
                    'danger',
                    'Le nom de la catégorie ne doit pas dépasser 100 caractères.'
                );

                $categorie->setNom($nom);
                $categorie->setDescription(
                    $description !== '' ? $description : null
                );

                return $this->render('admin/categorie/new.html.twig', [
                    'categorie' => $categorie,
                ]);
            }

            $existante = $categorieRepository->findOneBy([
                'nom' => $nom,
            ]);

            if ($existante !== null) {
                $this->addFlash(
                    'danger',
                    'Cette catégorie existe déjà.'
                );

                $categorie->setNom($nom);
                $categorie->setDescription(
                    $description !== '' ? $description : null
                );

                return $this->render('admin/categorie/new.html.twig', [
                    'categorie' => $categorie,
                ]);
            }

            $categorie->setNom($nom);
            $categorie->setDescription(
                $description !== '' ? $description : null
            );
            $categorie->setActif(true);
            $categorie->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($categorie);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'La catégorie a été créée avec succès.'
            );

            return $this->redirectToRoute('app_categorie_index');
        }

        return $this->render('admin/categorie/new.html.twig', [
            'categorie' => $categorie,
        ]);
    }


    /**
     * Modifier une catégorie
     */
    #[Route('/{id}/modifier', name: 'app_categorie_edit', methods: ['GET', 'POST'])]
    public function edit(
        Categorie $categorie,
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieRepository $categorieRepository
    ): Response {
        if ($request->isMethod('POST')) {

            if (!$this->isCsrfTokenValid(
                'modifier_categorie_' . $categorie->getId(),
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Jeton CSRF invalide.'
                );
            }

            $nom = trim((string) $request->request->get('nom'));
            $description = trim((string) $request->request->get('description'));

            if ($nom === '') {
                $this->addFlash(
                    'danger',
                    'Le nom de la catégorie est obligatoire.'
                );

                $categorie->setNom($nom);
                $categorie->setDescription(
                    $description !== '' ? $description : null
                );

                return $this->render('admin/categorie/edit.html.twig', [
                    'categorie' => $categorie,
                ]);
            }

            if (mb_strlen($nom) > 100) {
                $this->addFlash(
                    'danger',
                    'Le nom de la catégorie ne doit pas dépasser 100 caractères.'
                );

                return $this->render('admin/categorie/edit.html.twig', [
                    'categorie' => $categorie,
                ]);
            }

            $existante = $categorieRepository->findOneBy([
                'nom' => $nom,
            ]);

            if (
                $existante !== null
                && $existante->getId() !== $categorie->getId()
            ) {
                $this->addFlash(
                    'danger',
                    'Une autre catégorie utilise déjà ce nom.'
                );

                return $this->render('admin/categorie/edit.html.twig', [
                    'categorie' => $categorie,
                ]);
            }

            $categorie->setNom($nom);
            $categorie->setDescription(
                $description !== '' ? $description : null
            );
            $categorie->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash(
                'success',
                'La catégorie a été modifiée avec succès.'
            );

            return $this->redirectToRoute('app_categorie_index');
        }

        return $this->render('admin/categorie/edit.html.twig', [
            'categorie' => $categorie,
        ]);
    }


    /**
     * Activer / désactiver une catégorie
     */
    #[Route(
        '/{id}/toggle',
        name: 'app_categorie_toggle',
        methods: ['POST']
    )]
    public function toggle(
        Categorie $categorie,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'toggle_categorie_' . $categorie->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $categorie->setActif(!$categorie->isActif());
        $categorie->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        if ($categorie->isActif()) {
            $this->addFlash(
                'success',
                'La catégorie « ' . $categorie->getNom() . ' » est maintenant active.'
            );
        } else {
            $this->addFlash(
                'warning',
                'La catégorie « ' . $categorie->getNom() . ' » est maintenant inactive.'
            );
        }

        return $this->redirectToRoute('app_categorie_index');
    }
}
