<?php

namespace App\Controller;

use App\Entity\Famille;
use App\Form\FamilleType;
use App\Repository\FamilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/familles')]
class FamilleController extends AbstractController
{
    /**
     * Liste des familles
     */
    #[Route('', name: 'app_famille_index', methods: ['GET'])]
    public function index(
        FamilleRepository $familleRepository
    ): Response {
        return $this->render('famille/index.html.twig', [
            'familles' => $familleRepository->findBy(
                [],
                ['nom' => 'ASC']
            ),
        ]);
    }

    /**
     * Ajouter une famille
     */
    #[Route('/nouveau', name: 'app_famille_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $famille = new Famille();

        $form = $this->createForm(FamilleType::class, $famille);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($famille);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'La famille a été ajoutée avec succès.'
            );

            return $this->redirectToRoute('app_famille_index');
        }

        return $this->render('famille/new.html.twig', [
            'famille' => $famille,
            'form' => $form,
        ]);
    }

    /**
     * Détails d'une famille
     */
    #[Route('/{id}', name: 'app_famille_show', methods: ['GET'])]
    public function show(
        Famille $famille
    ): Response {
        return $this->render('famille/show.html.twig', [
            'famille' => $famille,
        ]);
    }

    /**
     * Modifier une famille
     */
    #[Route('/{id}/modifier', name: 'app_famille_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Famille $famille,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(FamilleType::class, $famille);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $famille->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash(
                'success',
                'La famille a été modifiée avec succès.'
            );

            return $this->redirectToRoute('app_famille_index');
        }

        return $this->render('famille/edit.html.twig', [
            'famille' => $famille,
            'form' => $form,
        ]);
    }

    /**
     * Activer / Désactiver une famille
     */
    #[Route(
        '/{id}/statut',
        name: 'app_famille_toggle',
        methods: ['POST']
    )]
    public function toggle(
        Request $request,
        Famille $famille,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'toggle' . $famille->getId(),
            $request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_famille_index');
        }

        $famille->setActif(!$famille->isActif());
        $famille->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash(
            'success',
            $famille->isActif()
                ? 'La famille a été activée.'
                : 'La famille a été désactivée.'
        );

        return $this->redirectToRoute('app_famille_index');
    }

    /**
     * Supprimer une famille
     */
    #[Route(
        '/{id}/supprimer',
        name: 'app_famille_delete',
        methods: ['POST']
    )]
    public function delete(
        Request $request,
        Famille $famille,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'delete' . $famille->getId(),
            $request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_famille_index');
        }

        if ($famille->getProduits()->count() > 0) {
            $this->addFlash(
                'warning',
                'Cette famille contient des produits. Elle ne peut pas être supprimée.'
            );

            return $this->redirectToRoute('app_famille_index');
        }

        $entityManager->remove($famille);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'La famille a été supprimée avec succès.'
        );

        return $this->redirectToRoute('app_famille_index');
    }
}
