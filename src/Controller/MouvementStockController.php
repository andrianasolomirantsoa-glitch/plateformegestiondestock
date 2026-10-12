<?php

namespace App\Controller;

use App\Entity\EntreeStock;
use App\Entity\SortieStock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/mouvements')]
#[IsGranted('ROLE_ADMIN')]
class MouvementStockController extends AbstractController
{
    #[Route('', name: 'app_mouvement_stock_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $recherche = trim((string) $request->query->get('q', ''));
        $type = strtoupper(trim((string) $request->query->get('type', 'TOUS')));

        if (!in_array($type, ['TOUS', 'ENTREE', 'SORTIE'], true)) {
            $type = 'TOUS';
        }

        $mouvements = [];

        /*
         * =========================================================
         * ENTRÉES STOCK
         * =========================================================
         */
        if ($type === 'TOUS' || $type === 'ENTREE') {

            /** @var EntreeStock[] $entrees */
            $entrees = $entityManager
                ->getRepository(EntreeStock::class)
                ->findAll();

            foreach ($entrees as $entree) {

                $produit = $entree->getProduit();
                $fournisseur = $entree->getFournisseur();

                $texteRecherche = implode(' ', array_filter([
                    $produit?->getNom(),
                    $produit?->getReference(),
                    $fournisseur?->getNom(),
                    $entree->getReferenceDocument(),
                    $entree->getCommentaire(),
                ]));

                if (
                    $recherche !== ''
                    && stripos($texteRecherche, $recherche) === false
                ) {
                    continue;
                }

                $prix = $entree->getPrixUnitaire();

                $valeur = 0;

                if ($prix !== null && is_numeric($prix)) {
                    $valeur = $entree->getQuantite() * (float) $prix;
                }

                $mouvements[] = [
                    'type' => 'ENTREE',
                    'id' => $entree->getId(),
                    'date' => $entree->getDateEntree(),
                    'produit' => $produit,
                    'partenaire' => $fournisseur,
                    'partenaireType' => 'FOURNISSEUR',
                    'quantite' => $entree->getQuantite(),
                    'prix' => $prix,
                    'valeur' => $valeur,
                    'reference' => $entree->getReferenceDocument(),
                    'commentaire' => $entree->getCommentaire(),
                    'utilisateur' => $entree->getUtilisateur(),
                ];
            }
        }


        /*
         * =========================================================
         * SORTIES STOCK
         * =========================================================
         */
        if ($type === 'TOUS' || $type === 'SORTIE') {

            /** @var SortieStock[] $sorties */
            $sorties = $entityManager
                ->getRepository(SortieStock::class)
                ->findAll();

            foreach ($sorties as $sortie) {

                $produit = $sortie->getProduit();
                $client = $sortie->getClient();

                $texteRecherche = implode(' ', array_filter([
                    $produit?->getNom(),
                    $produit?->getReference(),
                    $client?->getNom(),
                    $client?->getPrenom(),
                    $sortie->getReferenceDocument(),
                    $sortie->getCommentaire(),
                ]));

                if (
                    $recherche !== ''
                    && stripos($texteRecherche, $recherche) === false
                ) {
                    continue;
                }

                $prix = $sortie->getPrixUnitaire();

                $valeur = 0;

                if ($prix !== null && is_numeric($prix)) {
                    $valeur = $sortie->getQuantite() * (float) $prix;
                }

                $mouvements[] = [
                    'type' => 'SORTIE',
                    'id' => $sortie->getId(),
                    'date' => $sortie->getDateSortie(),
                    'produit' => $produit,
                    'partenaire' => $client,
                    'partenaireType' => 'CLIENT',
                    'quantite' => $sortie->getQuantite(),
                    'prix' => $prix,
                    'valeur' => $valeur,
                    'reference' => $sortie->getReferenceDocument(),
                    'commentaire' => $sortie->getCommentaire(),
                    'utilisateur' => $sortie->getUtilisateur(),
                ];
            }
        }


        /*
         * =========================================================
         * TRI : PLUS RÉCENT EN PREMIER
         * =========================================================
         */
        usort(
            $mouvements,
            static function (array $a, array $b): int {

                $dateA = $a['date']?->getTimestamp() ?? 0;
                $dateB = $b['date']?->getTimestamp() ?? 0;

                return $dateB <=> $dateA;
            }
        );


        /*
         * =========================================================
         * STATISTIQUES
         * =========================================================
         */
        $nombreMouvements = count($mouvements);

        $nombreEntrees = 0;
        $nombreSorties = 0;

        $quantiteEntree = 0;
        $quantiteSortie = 0;

        $valeurEntree = 0;
        $valeurSortie = 0;

        foreach ($mouvements as $mouvement) {

            if ($mouvement['type'] === 'ENTREE') {

                $nombreEntrees++;
                $quantiteEntree += $mouvement['quantite'];
                $valeurEntree += $mouvement['valeur'];

            } else {

                $nombreSorties++;
                $quantiteSortie += $mouvement['quantite'];
                $valeurSortie += $mouvement['valeur'];
            }
        }


        /*
         * =========================================================
         * RENDU
         * =========================================================
         */
        return $this->render('admin/mouvement_stock/index.html.twig', [
            'mouvements' => $mouvements,
            'recherche' => $recherche,
            'type' => $type,

            'nombreMouvements' => $nombreMouvements,
            'nombreEntrees' => $nombreEntrees,
            'nombreSorties' => $nombreSorties,

            'quantiteEntree' => $quantiteEntree,
            'quantiteSortie' => $quantiteSortie,

            'valeurEntree' => $valeurEntree,
            'valeurSortie' => $valeurSortie,
        ]);
    }
}

