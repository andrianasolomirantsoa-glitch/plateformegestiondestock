<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CommandeRepository;
use App\Repository\LivraisonRepository;
use App\Repository\PaiementRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client')]
#[IsGranted('ROLE_CLIENT')]
class ClientController extends AbstractController
{
    #[Route('', name: 'app_client_home', methods: ['GET'])]
    public function index(
        CommandeRepository $commandeRepository,
        PaiementRepository $paiementRepository,
        LivraisonRepository $livraisonRepository,
        CartService $cartService
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        /*
         * Toutes les commandes du client connecté.
         */
        $commandes = $commandeRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        /*
         * Limiter l'affichage aux 5 dernières commandes.
         */
        $dernieresCommandes = array_slice(
            $commandes,
            0,
            5
        );

        $nombreCommandes = count($commandes);
        $commandesEnAttente = 0;
        $commandesConfirmees = 0;
        $totalCommandes = 0.0;

        $paiementsPayes = 0;
        $paiementsEnAttente = 0;

        $livraisonsEnCours = 0;
        $livraisonsLivrees = 0;

        $paiements = [];
        $livraisons = [];

        foreach ($commandes as $commande) {

            /*
             * Statistiques commandes.
             */
            if ($commande->getStatut() === 'EN_ATTENTE') {
                $commandesEnAttente++;
            }

            if ($commande->getStatut() === 'CONFIRMEE') {
                $commandesConfirmees++;
            }

            $totalCommandes += (float) $commande->getTotal();

            /*
             * Paiement associé.
             */
            $paiement = $paiementRepository->findOneBy([
                'commande' => $commande,
            ]);

            $paiements[$commande->getId()] = $paiement;

            if ($paiement !== null) {

                if ($paiement->getStatut() === 'PAYE') {
                    $paiementsPayes++;
                }

                if ($paiement->getStatut() === 'EN_ATTENTE') {
                    $paiementsEnAttente++;
                }
            }

            /*
             * Livraison associée.
             */
            $livraison = $livraisonRepository->findOneBy([
                'commande' => $commande,
            ]);

            $livraisons[$commande->getId()] = $livraison;

            if ($livraison !== null) {

                if ($livraison->getStatut() === 'EN_COURS') {
                    $livraisonsEnCours++;
                }

                if ($livraison->getStatut() === 'LIVREE') {
                    $livraisonsLivrees++;
                }
            }
        }

        return $this->render(
            'client/index.html.twig',
            [
                'user' => $user,

                'commandes' => $commandes,
                'dernieresCommandes' => $dernieresCommandes,

                'nombreCommandes' => $nombreCommandes,
                'commandesEnAttente' => $commandesEnAttente,
                'commandesConfirmees' => $commandesConfirmees,
                'totalCommandes' => $totalCommandes,

                'paiementsPayes' => $paiementsPayes,
                'paiementsEnAttente' => $paiementsEnAttente,

                'livraisonsEnCours' => $livraisonsEnCours,
                'livraisonsLivrees' => $livraisonsLivrees,

                'paiements' => $paiements,
                'livraisons' => $livraisons,

                'panierQuantity' => $cartService->getTotalQuantity(),
                'panierTotal' => $cartService->getTotal(),
            ]
        );
    }
}

