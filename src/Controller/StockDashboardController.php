<?php

namespace App\Controller;

use App\Entity\EntreeStock;
use App\Entity\Produit;
use App\Entity\SortieStock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tableau-stock')]
#[IsGranted('ROLE_ADMIN')]
class StockDashboardController extends AbstractController
{
    #[Route('', name: 'app_stock_dashboard', methods: ['GET'])]
    public function index(
        EntityManagerInterface $entityManager
    ): Response {
        /*
         * =========================================================
         * PRODUITS
         * =========================================================
         */

        /** @var Produit[] $produits */
        $produits = $entityManager
            ->getRepository(Produit::class)
            ->findAll();

        $nombreProduits = count($produits);

        $produitsActifs = 0;
        $produitsInactifs = 0;

        $quantiteTotaleStock = 0;

        $valeurAchatStock = 0;
        $valeurVenteStock = 0;

        $produitsStockFaible = [];
        $produitsStockEpuise = [];

        foreach ($produits as $produit) {

            $quantite = $produit->getQuantiteStock();
            $seuil = $produit->getSeuilAlerte();

            $prixAchat = $produit->getPrixAchat();
            $prixVente = $produit->getPrixVente();

            /*
             * Actif / Inactif
             */
            if ($produit->isActif()) {
                $produitsActifs++;
            } else {
                $produitsInactifs++;
            }

            /*
             * Quantité totale
             */
            $quantiteTotaleStock += $quantite;

            /*
             * Valeur du stock au prix d'achat
             */
            if ($prixAchat !== null && is_numeric($prixAchat)) {
                $valeurAchatStock += $quantite * (float) $prixAchat;
            }

            /*
             * Valeur potentielle au prix de vente
             */
            if ($prixVente !== null && is_numeric($prixVente)) {
                $valeurVenteStock += $quantite * (float) $prixVente;
            }

            /*
             * Stock épuisé
             */
            if ($quantite <= 0) {
                $produitsStockEpuise[] = $produit;
            }

            /*
             * Stock faible
             *
             * On considère qu'un produit est en alerte lorsque
             * son stock est inférieur ou égal à son seuil.
             */
            elseif (
                $seuil > 0
                && $quantite <= $seuil
            ) {
                $produitsStockFaible[] = $produit;
            }
        }


        /*
         * Tri des produits en alerte :
         * les stocks les plus faibles en premier.
         */
        usort(
            $produitsStockFaible,
            static function (Produit $a, Produit $b): int {
                return $a->getQuantiteStock() <=> $b->getQuantiteStock();
            }
        );


        /*
         * =========================================================
         * ENTRÉES STOCK
         * =========================================================
         */

        /** @var EntreeStock[] $entrees */
        $entrees = $entityManager
            ->getRepository(EntreeStock::class)
            ->findAll();

        $nombreEntrees = count($entrees);

        $quantiteEntreeTotale = 0;
        $valeurEntreeTotale = 0;

        foreach ($entrees as $entree) {

            $quantiteEntreeTotale += $entree->getQuantite();

            $prix = $entree->getPrixUnitaire();

            if ($prix !== null && is_numeric($prix)) {
                $valeurEntreeTotale +=
                    $entree->getQuantite() * (float) $prix;
            }
        }


        /*
         * =========================================================
         * SORTIES STOCK
         * =========================================================
         */

        /** @var SortieStock[] $sorties */
        $sorties = $entityManager
            ->getRepository(SortieStock::class)
            ->findAll();

        $nombreSorties = count($sorties);

        $quantiteSortieTotale = 0;
        $valeurSortieTotale = 0;

        foreach ($sorties as $sortie) {

            $quantiteSortieTotale += $sortie->getQuantite();

            $prix = $sortie->getPrixUnitaire();

            if ($prix !== null && is_numeric($prix)) {
                $valeurSortieTotale +=
                    $sortie->getQuantite() * (float) $prix;
            }
        }


        /*
         * =========================================================
         * DERNIÈRES ENTRÉES
         * =========================================================
         */

        usort(
            $entrees,
            static function (EntreeStock $a, EntreeStock $b): int {

                $dateA = $a->getDateEntree()?->getTimestamp() ?? 0;
                $dateB = $b->getDateEntree()?->getTimestamp() ?? 0;

                return $dateB <=> $dateA;
            }
        );

        $dernieresEntrees = array_slice($entrees, 0, 5);


        /*
         * =========================================================
         * DERNIÈRES SORTIES
         * =========================================================
         */

        usort(
            $sorties,
            static function (SortieStock $a, SortieStock $b): int {

                $dateA = $a->getDateSortie()?->getTimestamp() ?? 0;
                $dateB = $b->getDateSortie()?->getTimestamp() ?? 0;

                return $dateB <=> $dateA;
            }
        );

        $dernieresSorties = array_slice($sorties, 0, 5);


        /*
         * =========================================================
         * MOUVEMENTS TOTAUX
         * =========================================================
         */

        $nombreMouvements = $nombreEntrees + $nombreSorties;

        $differenceQuantite =
            $quantiteEntreeTotale - $quantiteSortieTotale;


        /*
         * =========================================================
         * PRODUITS LES PLUS STOCKÉS
         * =========================================================
         */

        $produitsAvecStock = array_filter(
            $produits,
            static function (Produit $produit): bool {
                return $produit->getQuantiteStock() > 0;
            }
        );

        usort(
            $produitsAvecStock,
            static function (Produit $a, Produit $b): int {
                return $b->getQuantiteStock() <=> $a->getQuantiteStock();
            }
        );

        $topStocks = array_slice($produitsAvecStock, 0, 5);


        /*
         * =========================================================
         * RENDU
         * =========================================================
         */

        return $this->render('admin/stock_dashboard/index.html.twig', [

            /*
             * Produits
             */
            'nombreProduits' => $nombreProduits,
            'produitsActifs' => $produitsActifs,
            'produitsInactifs' => $produitsInactifs,

            /*
             * Stock
             */
            'quantiteTotaleStock' => $quantiteTotaleStock,
            'valeurAchatStock' => $valeurAchatStock,
            'valeurVenteStock' => $valeurVenteStock,

            /*
             * Alertes
             */
            'produitsStockFaible' => $produitsStockFaible,
            'produitsStockEpuise' => $produitsStockEpuise,

            /*
             * Entrées
             */
            'nombreEntrees' => $nombreEntrees,
            'quantiteEntreeTotale' => $quantiteEntreeTotale,
            'valeurEntreeTotale' => $valeurEntreeTotale,
            'dernieresEntrees' => $dernieresEntrees,

            /*
             * Sorties
             */
            'nombreSorties' => $nombreSorties,
            'quantiteSortieTotale' => $quantiteSortieTotale,
            'valeurSortieTotale' => $valeurSortieTotale,
            'dernieresSorties' => $dernieresSorties,

            /*
             * Mouvements
             */
            'nombreMouvements' => $nombreMouvements,
            'differenceQuantite' => $differenceQuantite,

            /*
             * Produits avec le plus de stock
             */
            'topStocks' => $topStocks,
        ]);
    }
}

