<?php

namespace App\Controller;

use App\Entity\SortieStock;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\ProduitRepository;
use App\Repository\SortieStockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/sorties')]
#[IsGranted('ROLE_ADMIN')]
class SortieStockController extends AbstractController
{
    /*
     * =========================================================
     * LISTE DES SORTIES
     * =========================================================
     */
    #[Route('', name: 'app_sortie_stock_index', methods: ['GET'])]
    public function index(
        Request $request,
        SortieStockRepository $sortieStockRepository
    ): Response {
        $recherche = trim((string) $request->query->get('q', ''));

        $sorties = $sortieStockRepository->findBy(
            [],
            ['dateSortie' => 'DESC']
        );

        /*
         * Recherche côté PHP pour rester simple,
         * comme pour le module Entrées.
         */
        if ($recherche !== '') {

            $rechercheMin = mb_strtolower($recherche);

            $sorties = array_filter(
                $sorties,
                function (SortieStock $sortie) use ($rechercheMin): bool {

                    $produitNom = $sortie->getProduit()?->getNom() ?? '';
                    $produitReference = $sortie->getProduit()?->getReference() ?? '';
                    $referenceDocument = $sortie->getReferenceDocument() ?? '';

                    $clientNom = '';
                    $clientPrenom = '';

                    if ($sortie->getClient() !== null) {
                        $clientNom = $sortie->getClient()->getNom() ?? '';
                        $clientPrenom = $sortie->getClient()->getPrenom() ?? '';
                    }

                    $texte = mb_strtolower(
                        $produitNom . ' ' .
                        $produitReference . ' ' .
                        $referenceDocument . ' ' .
                        $clientNom . ' ' .
                        $clientPrenom
                    );

                    return mb_strpos($texte, $rechercheMin) !== false;
                }
            );
        }

        /*
         * Statistiques
         */
        $nombreSorties = count($sorties);
        $quantiteTotale = 0;
        $valeurTotale = 0.0;

        foreach ($sorties as $sortie) {

            $quantite = $sortie->getQuantite();

            $quantiteTotale += $quantite;

            $prixUnitaire = (float) ($sortie->getPrixUnitaire() ?? 0);

            $valeurTotale += $quantite * $prixUnitaire;
        }

        return $this->render('admin/sortie_stock/index.html.twig', [
            'sorties' => $sorties,
            'recherche' => $recherche,
            'nombreSorties' => $nombreSorties,
            'quantiteTotale' => $quantiteTotale,
            'valeurTotale' => $valeurTotale,
        ]);
    }


    /*
     * =========================================================
     * NOUVELLE SORTIE
     * =========================================================
     */
    #[Route('/nouvelle', name: 'app_sortie_stock_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository,
        ClientRepository $clientRepository
    ): Response {

        $sortie = new SortieStock();

        /*
         * Produits actifs uniquement
         */
        $produits = $produitRepository->findBy(
            ['actif' => true],
            ['nom' => 'ASC']
        );

        /*
         * Clients actifs uniquement
         */
        $clients = $clientRepository->findBy(
            ['actif' => true],
            ['nom' => 'ASC']
        );

        if ($request->isMethod('POST')) {

            /*
             * CSRF
             */
            if (!$this->isCsrfTokenValid(
                'creer_sortie_stock',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Token CSRF invalide.'
                );
            }

            /*
             * Produit
             */
            $produitId = (int) $request->request->get('produit');

            $produit = $produitRepository->find($produitId);

            if ($produit === null || !$produit->isActif()) {

                $this->addFlash(
                    'error',
                    'Le produit sélectionné est invalide ou inactif.'
                );

                return $this->redirectToRoute('app_sortie_stock_new');
            }


            /*
             * Quantité
             */
            $quantite = (int) $request->request->get('quantite', 0);

            if ($quantite <= 0) {

                $this->addFlash(
                    'error',
                    'La quantité doit être supérieure à zéro.'
                );

                return $this->redirectToRoute('app_sortie_stock_new');
            }


            /*
             * Vérification du stock
             */
            $stockActuel = $produit->getQuantiteStock();

            if ($quantite > $stockActuel) {

                $this->addFlash(
                    'error',
                    sprintf(
                        'Stock insuffisant. Stock disponible : %d pièce(s).',
                        $stockActuel
                    )
                );

                return $this->redirectToRoute('app_sortie_stock_new');
            }


            /*
             * Prix unitaire
             */
            $prixUnitaire = trim(
                (string) $request->request->get('prixUnitaire', '0')
            );

            if ($prixUnitaire === '') {
                $prixUnitaire = '0';
            }

            if (!is_numeric($prixUnitaire) || (float) $prixUnitaire < 0) {

                $this->addFlash(
                    'error',
                    'Le prix unitaire doit être un nombre positif ou nul.'
                );

                return $this->redirectToRoute('app_sortie_stock_new');
            }


            /*
             * Client facultatif
             */
            $client = null;

            $clientId = trim(
                (string) $request->request->get('client', '')
            );

            if ($clientId !== '') {

                $client = $clientRepository->find((int) $clientId);

                if ($client === null || !$client->isActif()) {

                    $this->addFlash(
                        'error',
                        'Le client sélectionné est invalide ou inactif.'
                    );

                    return $this->redirectToRoute('app_sortie_stock_new');
                }
            }


            /*
             * Date
             */
            $dateSortie = new \DateTimeImmutable();

            $dateInput = trim(
                (string) $request->request->get('dateSortie', '')
            );

            if ($dateInput !== '') {

                $date = \DateTimeImmutable::createFromFormat(
                    'Y-m-d\TH:i',
                    $dateInput
                );

                if ($date === false) {

                    $this->addFlash(
                        'error',
                        'La date de sortie est invalide.'
                    );

                    return $this->redirectToRoute('app_sortie_stock_new');
                }

                $dateSortie = $date;
            }


            /*
             * Référence document
             */
            $referenceDocument = trim(
                (string) $request->request->get(
                    'referenceDocument',
                    ''
                )
            );

            if ($referenceDocument === '') {
                $referenceDocument = null;
            }


            /*
             * Commentaire
             */
            $commentaire = trim(
                (string) $request->request->get(
                    'commentaire',
                    ''
                )
            );

            if ($commentaire === '') {
                $commentaire = null;
            }


            /*
             * Utilisateur connecté
             */
            $user = $this->getUser();

            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }


            /*
             * Création de la sortie
             */
            $sortie
                ->setProduit($produit)
                ->setClient($client)
                ->setUtilisateur($user)
                ->setQuantite($quantite)
                ->setPrixUnitaire((string) $prixUnitaire)
                ->setDateSortie($dateSortie)
                ->setReferenceDocument($referenceDocument)
                ->setCommentaire($commentaire);


            /*
             * Mise à jour du stock
             */
            $produit->setQuantiteStock(
                $stockActuel - $quantite
            );


            $entityManager->persist($sortie);
            $entityManager->flush();


            $this->addFlash(
                'success',
                sprintf(
                    'Sortie de %d pièce(s) enregistrée. Nouveau stock : %d pièce(s).',
                    $quantite,
                    $produit->getQuantiteStock()
                )
            );

            return $this->redirectToRoute(
                'app_sortie_stock_index'
            );
        }


        return $this->render(
            'admin/sortie_stock/new.html.twig',
            [
                'sortie' => $sortie,
                'produits' => $produits,
                'clients' => $clients,
            ]
        );
    }


    /*
     * =========================================================
     * MODIFIER UNE SORTIE
     * =========================================================
     */
    #[Route(
        '/{id}/modifier',
        name: 'app_sortie_stock_edit',
        methods: ['GET', 'POST']
    )]
    public function edit(
        SortieStock $sortie,
        Request $request,
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository,
        ClientRepository $clientRepository
    ): Response {

        $ancienneQuantite = $sortie->getQuantite();
        $ancienProduit = $sortie->getProduit();

        /*
         * Produits actifs + ancien produit
         *
         * Cela permet de modifier une sortie même si
         * l'ancien produit est devenu inactif.
         */
        $produits = $produitRepository->findBy(
            [],
            ['nom' => 'ASC']
        );

        /*
         * Clients actifs + ancien client
         */
        $clients = $clientRepository->findBy(
            [],
            ['nom' => 'ASC']
        );


        if ($request->isMethod('POST')) {

            /*
             * CSRF
             */
            if (!$this->isCsrfTokenValid(
                'modifier_sortie_stock_' . $sortie->getId(),
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Token CSRF invalide.'
                );
            }


            /*
             * Nouveau produit
             */
            $nouveauProduitId =
                (int) $request->request->get('produit');

            $nouveauProduit =
                $produitRepository->find($nouveauProduitId);


            if (
                $nouveauProduit === null ||
                !$nouveauProduit->isActif()
            ) {

                $this->addFlash(
                    'error',
                    'Le produit sélectionné est invalide ou inactif.'
                );

                return $this->redirectToRoute(
                    'app_sortie_stock_edit',
                    ['id' => $sortie->getId()]
                );
            }


            /*
             * Nouvelle quantité
             */
            $nouvelleQuantite =
                (int) $request->request->get(
                    'quantite',
                    0
                );


            if ($nouvelleQuantite <= 0) {

                $this->addFlash(
                    'error',
                    'La quantité doit être supérieure à zéro.'
                );

                return $this->redirectToRoute(
                    'app_sortie_stock_edit',
                    ['id' => $sortie->getId()]
                );
            }


            /*
             * =====================================================
             * MÊME PRODUIT
             * =====================================================
             *
             * Stock actuel contient déjà l'ancienne sortie.
             *
             * Nouveau stock :
             *
             * stock actuel
             * + ancienne quantité
             * - nouvelle quantité
             */
            if ($nouveauProduit->getId() === $ancienProduit->getId()) {

                $stockActuel =
                    $nouveauProduit->getQuantiteStock();

                $nouveauStock =
                    $stockActuel
                    + $ancienneQuantite
                    - $nouvelleQuantite;


                if ($nouveauStock < 0) {

                    $this->addFlash(
                        'error',
                        sprintf(
                            'Stock insuffisant. Stock disponible pour cette modification : %d pièce(s).',
                            $stockActuel + $ancienneQuantite
                        )
                    );

                    return $this->redirectToRoute(
                        'app_sortie_stock_edit',
                        ['id' => $sortie->getId()]
                    );
                }


                $nouveauProduit->setQuantiteStock(
                    $nouveauStock
                );

            }


            /*
             * =====================================================
             * CHANGEMENT DE PRODUIT
             * =====================================================
             *
             * 1. On rend l'ancienne quantité à l'ancien produit.
             * 2. On vérifie le stock du nouveau produit.
             * 3. On retire la nouvelle quantité du nouveau produit.
             */
            else {

                $stockNouveauProduit =
                    $nouveauProduit->getQuantiteStock();


                if ($nouvelleQuantite > $stockNouveauProduit) {

                    $this->addFlash(
                        'error',
                        sprintf(
                            'Stock insuffisant pour le nouveau produit. Stock disponible : %d pièce(s).',
                            $stockNouveauProduit
                        )
                    );

                    return $this->redirectToRoute(
                        'app_sortie_stock_edit',
                        ['id' => $sortie->getId()]
                    );
                }


                /*
                 * Restaurer l'ancien stock
                 */
                $ancienProduit->setQuantiteStock(
                    $ancienProduit->getQuantiteStock()
                    + $ancienneQuantite
                );


                /*
                 * Retirer la nouvelle quantité
                 */
                $nouveauProduit->setQuantiteStock(
                    $stockNouveauProduit
                    - $nouvelleQuantite
                );
            }


            /*
             * Prix unitaire
             */
            $prixUnitaire = trim(
                (string) $request->request->get(
                    'prixUnitaire',
                    '0'
                )
            );

            if ($prixUnitaire === '') {
                $prixUnitaire = '0';
            }

            if (!is_numeric($prixUnitaire) || (float) $prixUnitaire < 0) {

                $this->addFlash(
                    'error',
                    'Le prix unitaire doit être un nombre positif ou nul.'
                );

                return $this->redirectToRoute(
                    'app_sortie_stock_edit',
                    ['id' => $sortie->getId()]
                );
            }


            /*
             * Client
             */
            $client = null;

            $clientId = trim(
                (string) $request->request->get(
                    'client',
                    ''
                )
            );

            if ($clientId !== '') {

                $client =
                    $clientRepository->find(
                        (int) $clientId
                    );

                if (
                    $client === null ||
                    !$client->isActif()
                ) {

                    $this->addFlash(
                        'error',
                        'Le client sélectionné est invalide ou inactif.'
                    );

                    return $this->redirectToRoute(
                        'app_sortie_stock_edit',
                        ['id' => $sortie->getId()]
                    );
                }
            }


            /*
             * Date
             */
            $dateSortie =
                $sortie->getDateSortie();

            $dateInput = trim(
                (string) $request->request->get(
                    'dateSortie',
                    ''
                )
            );

            if ($dateInput !== '') {

                $date = \DateTimeImmutable::createFromFormat(
                    'Y-m-d\TH:i',
                    $dateInput
                );

                if ($date === false) {

                    $this->addFlash(
                        'error',
                        'La date de sortie est invalide.'
                    );

                    return $this->redirectToRoute(
                        'app_sortie_stock_edit',
                        ['id' => $sortie->getId()]
                    );
                }

                $dateSortie = $date;
            }


            /*
             * Référence document
             */
            $referenceDocument =
                trim(
                    (string) $request->request->get(
                        'referenceDocument',
                        ''
                    )
                );

            if ($referenceDocument === '') {
                $referenceDocument = null;
            }


            /*
             * Commentaire
             */
            $commentaire =
                trim(
                    (string) $request->request->get(
                        'commentaire',
                        ''
                    )
                );

            if ($commentaire === '') {
                $commentaire = null;
            }


            /*
             * Mise à jour de l'entité
             */
            $sortie
                ->setProduit($nouveauProduit)
                ->setClient($client)
                ->setQuantite($nouvelleQuantite)
                ->setPrixUnitaire((string) $prixUnitaire)
                ->setDateSortie($dateSortie)
                ->setReferenceDocument($referenceDocument)
                ->setCommentaire($commentaire);


            $entityManager->flush();


            $this->addFlash(
                'success',
                'La sortie de stock a été modifiée avec succès.'
            );

            return $this->redirectToRoute(
                'app_sortie_stock_index'
            );
        }


        return $this->render(
            'admin/sortie_stock/edit.html.twig',
            [
                'sortie' => $sortie,
                'produits' => $produits,
                'clients' => $clients,
            ]
        );
    }
}

