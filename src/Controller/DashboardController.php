<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use App\Repository\LivraisonRepository;
use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        CommandeRepository $commandeRepository,
        PaiementRepository $paiementRepository,
        LivraisonRepository $livraisonRepository
    ): Response {
        $commandesEnAttente = $commandeRepository->count([
            'statut' => 'EN_ATTENTE',
        ]);

        $commandesConfirmees = $commandeRepository->count([
            'statut' => 'CONFIRMEE',
        ]);

        $commandesAnnulees = $commandeRepository->count([
            'statut' => 'ANNULEE',
        ]);

        $paiementsEnAttente = $paiementRepository->count([
            'statut' => 'EN_ATTENTE',
        ]);

        $paiementsPayes = $paiementRepository->count([
            'statut' => 'PAYE',
        ]);

        $paiementsRefuses = $paiementRepository->count([
            'statut' => 'REFUSE',
        ]);

        $livraisonsEnAttente = $livraisonRepository->count([
            'statut' => 'EN_ATTENTE',
        ]);

        $livraisonsEnCours = $livraisonRepository->count([
            'statut' => 'EN_COURS',
        ]);

        $livraisonsLivrees = $livraisonRepository->count([
            'statut' => 'LIVREE',
        ]);

        return $this->render(
            'dashboard/index.html.twig',
            [
                'commandesEnAttente' => $commandesEnAttente,
                'commandesConfirmees' => $commandesConfirmees,
                'commandesAnnulees' => $commandesAnnulees,

                'paiementsEnAttente' => $paiementsEnAttente,
                'paiementsPayes' => $paiementsPayes,
                'paiementsRefuses' => $paiementsRefuses,

                'livraisonsEnAttente' => $livraisonsEnAttente,
                'livraisonsEnCours' => $livraisonsEnCours,
                'livraisonsLivrees' => $livraisonsLivrees,
            ]
        );
    }
}

