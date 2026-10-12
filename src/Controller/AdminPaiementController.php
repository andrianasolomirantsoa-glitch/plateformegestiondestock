<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/paiements')]
#[IsGranted('ROLE_ADMIN')]
class AdminPaiementController extends AbstractController
{
    #[Route('', name: 'app_admin_paiement_index', methods: ['GET'])]
    public function index(
        PaiementRepository $paiementRepository
    ): Response {
        $paiements = $paiementRepository->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        return $this->render(
            'admin/paiement/index.html.twig',
            [
                'paiements' => $paiements,
            ]
        );
    }

    #[Route(
        '/{id}/statut/{statut}',
        name: 'app_admin_paiement_status',
        methods: ['POST']
    )]
    public function changeStatus(
        Paiement $paiement,
        string $statut,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if (!$this->isCsrfTokenValid(
            'paiement_statut_' . $paiement->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $statutsAutorises = [
            'PAYE',
            'REFUSE',
        ];

        if (!in_array($statut, $statutsAutorises, true)) {
            $this->addFlash(
                'danger',
                'Statut de paiement invalide.'
            );

            return $this->redirectToRoute(
                'app_admin_paiement_index'
            );
        }

        if ($paiement->getStatut() !== 'EN_ATTENTE') {
            $this->addFlash(
                'warning',
                'Ce paiement a déjà été traité.'
            );

            return $this->redirectToRoute(
                'app_admin_paiement_index'
            );
        }

        $paiement->setStatut($statut);
        $paiement->setUpdatedAt(
            new \DateTimeImmutable()
        );

        $entityManager->flush();

        if ($statut === 'PAYE') {
            $this->addFlash(
                'success',
                'Le paiement a été validé avec succès.'
            );
        } else {
            $this->addFlash(
                'warning',
                'Le paiement a été refusé.'
            );
        }

        return $this->redirectToRoute(
            'app_admin_paiement_index'
        );
    }
}

