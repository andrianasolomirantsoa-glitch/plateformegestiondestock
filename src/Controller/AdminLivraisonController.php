<?php

namespace App\Controller;

use App\Entity\Livraison;
use App\Repository\LivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/livraisons')]
#[IsGranted('ROLE_ADMIN')]
class AdminLivraisonController extends AbstractController
{
    #[Route(
        '',
        name: 'app_admin_livraison_index',
        methods: ['GET']
    )]
    public function index(
        LivraisonRepository $livraisonRepository
    ): Response {
        $livraisons = $livraisonRepository->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        return $this->render(
            'admin/livraison/index.html.twig',
            [
                'livraisons' => $livraisons,
            ]
        );
    }

    #[Route(
        '/{id}/statut/{statut}',
        name: 'app_admin_livraison_status',
        methods: ['POST']
    )]
    public function changeStatus(
        Livraison $livraison,
        string $statut,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if (!$this->isCsrfTokenValid(
            'livraison_statut_' . $livraison->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $statutsAutorises = [
            'EN_COURS',
            'LIVREE',
            'ANNULEE',
        ];

        if (!in_array($statut, $statutsAutorises, true)) {
            $this->addFlash(
                'danger',
                'Statut de livraison invalide.'
            );

            return $this->redirectToRoute(
                'app_admin_livraison_index'
            );
        }

        $ancienStatut = $livraison->getStatut();

        /*
         * Vérification des transitions autorisées.
         */
        $transitionsAutorisees = [
            'EN_ATTENTE' => [
                'EN_COURS',
                'ANNULEE',
            ],
            'EN_COURS' => [
                'LIVREE',
                'ANNULEE',
            ],
        ];

        if (
            !isset($transitionsAutorisees[$ancienStatut]) ||
            !in_array(
                $statut,
                $transitionsAutorisees[$ancienStatut],
                true
            )
        ) {
            $this->addFlash(
                'warning',
                'Cette transition de livraison n’est pas autorisée.'
            );

            return $this->redirectToRoute(
                'app_admin_livraison_index'
            );
        }

        /*
         * Enregistrer automatiquement la date de livraison.
         */
        if ($statut === 'LIVREE') {
            $livraison->setDateLivraison(
                new \DateTimeImmutable()
            );
        }

        $livraison->setStatut($statut);

        $livraison->setUpdatedAt(
            new \DateTimeImmutable()
        );

        $entityManager->flush();

        if ($statut === 'EN_COURS') {
            $this->addFlash(
                'success',
                'La livraison est maintenant en cours.'
            );
        } elseif ($statut === 'LIVREE') {
            $this->addFlash(
                'success',
                'La livraison a été marquée comme livrée.'
            );
        } else {
            $this->addFlash(
                'warning',
                'La livraison a été annulée.'
            );
        }

        return $this->redirectToRoute(
            'app_admin_livraison_index'
        );
    }
}