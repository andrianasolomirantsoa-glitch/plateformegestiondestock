<?php

namespace App\Controller;

use App\Entity\EntreeStock;
use App\Entity\Magasin;
use App\Entity\Produit;
use App\Entity\StockMagasin;
use App\Entity\User;
use App\Repository\EntreeStockRepository;
use App\Repository\FournisseurRepository;
use App\Repository\MagasinRepository;
use App\Repository\ProduitRepository;
use App\Repository\StockMagasinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/entrees')]
#[IsGranted('ROLE_ADMIN')]
class EntreeStockController extends AbstractController
{
    /**
     * Liste des entrées de stock
     */
    #[Route('', name: 'app_entree_stock_index', methods: ['GET'])]
    public function index(
        EntreeStockRepository $entreeStockRepository,
        Request $request
    ): Response {
        $search = trim((string) $request->query->get('search', ''));

        $entrees = $entreeStockRepository->findBy(
            [],
            ['dateEntree' => 'DESC']
        );

        if ($search !== '') {
            $needle = mb_strtolower($search);

            $entrees = array_values(
                array_filter(
                    $entrees,
                    static function (EntreeStock $entree) use ($needle): bool {
                        $produitNom = mb_strtolower(
                            $entree->getProduit()?->getNom() ?? ''
                        );

                        $produitReference = mb_strtolower(
                            $entree->getProduit()?->getReference() ?? ''
                        );

                        $fournisseurNom = mb_strtolower(
                            $entree->getFournisseur()?->getNom() ?? ''
                        );

                        $referenceDocument = mb_strtolower(
                            $entree->getReferenceDocument() ?? ''
                        );

                        $magasinNom = mb_strtolower(
                            $entree->getMagasin()?->getNom() ?? ''
                        );

                        return str_contains($produitNom, $needle)
                            || str_contains($produitReference, $needle)
                            || str_contains($fournisseurNom, $needle)
                            || str_contains($referenceDocument, $needle)
                            || str_contains($magasinNom, $needle);
                    }
                )
            );
        }

        $totalEntrees = count($entrees);
        $quantiteTotale = 0;
        $valeurTotale = 0.0;

        foreach ($entrees as $entree) {
            $quantiteTotale += $entree->getQuantite();

            $valeurTotale +=
                $entree->getQuantite()
                * (float) ($entree->getPrixUnitaire() ?? 0);
        }

        return $this->render('admin/entree_stock/index.html.twig', [
            'entrees' => $entrees,
            'search' => $search,
            'totalEntrees' => $totalEntrees,
            'quantiteTotale' => $quantiteTotale,
            'valeurTotale' => $valeurTotale,
        ]);
    }

    /**
     * Nouvelle entrée de stock
     */
    #[Route('/nouvelle', name: 'app_entree_stock_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository,
        FournisseurRepository $fournisseurRepository,
        MagasinRepository $magasinRepository,
        StockMagasinRepository $stockMagasinRepository
    ): Response {
        $entree = new EntreeStock();

        $produits = $produitRepository->findBy(
            ['actif' => true],
            ['nom' => 'ASC']
        );

        $fournisseurs = $fournisseurRepository->findBy(
            ['actif' => true],
            ['nom' => 'ASC']
        );

        $magasins = $magasinRepository->findBy(
            ['actif' => true],
            ['nom' => 'ASC']
        );

        $stocksParProduitMagasin = $this->construireCarteDesStocks(
            $stockMagasinRepository
        );

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'creer_entree_stock',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Jeton CSRF invalide.'
                );
            }

            // PRODUIT
            $produitId = (int) $request->request->get('produit_id');
            $produit = $produitRepository->find($produitId);

            if (!$produit instanceof Produit || !$produit->isActif()) {
                $this->addFlash(
                    'danger',
                    'Veuillez sélectionner un produit actif valide.'
                );

                return $this->render('admin/entree_stock/new.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                    'magasins' => $magasins,
                    'stocksParProduitMagasin' => $stocksParProduitMagasin,
                ]);
            }

            // MAGASIN
            $magasinId = (int) $request->request->get('magasin_id');
            $magasin = $magasinRepository->find($magasinId);

            if (!$magasin instanceof Magasin || !$magasin->isActif()) {
                $this->addFlash(
                    'danger',
                    'Veuillez sélectionner un magasin actif valide.'
                );

                return $this->render('admin/entree_stock/new.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                    'magasins' => $magasins,
                    'stocksParProduitMagasin' => $stocksParProduitMagasin,
                ]);
            }

            // FOURNISSEUR
            $fournisseur = null;
            $fournisseurId = (int) $request->request->get('fournisseur_id');

            if ($fournisseurId > 0) {
                $fournisseur = $fournisseurRepository->find($fournisseurId);

                if (!$fournisseur || !$fournisseur->isActif()) {
                    $this->addFlash(
                        'danger',
                        'Le fournisseur sélectionné est introuvable ou inactif.'
                    );

                    return $this->render('admin/entree_stock/new.html.twig', [
                        'entree' => $entree,
                        'produits' => $produits,
                        'fournisseurs' => $fournisseurs,
                        'magasins' => $magasins,
                        'stocksParProduitMagasin' => $stocksParProduitMagasin,
                    ]);
                }
            }

            // QUANTITÉ
            $quantite = (int) $request->request->get('quantite');

            if ($quantite <= 0) {
                $this->addFlash(
                    'danger',
                    'La quantité doit être supérieure à zéro.'
                );

                return $this->render('admin/entree_stock/new.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                    'magasins' => $magasins,
                    'stocksParProduitMagasin' => $stocksParProduitMagasin,
                ]);
            }

            // PRIX UNITAIRE
            $prixUnitaireInput = str_replace(
                ',',
                '.',
                trim((string) $request->request->get('prix_unitaire'))
            );

            if (
                $prixUnitaireInput === ''
                || !is_numeric($prixUnitaireInput)
                || (float) $prixUnitaireInput < 0
            ) {
                $this->addFlash(
                    'danger',
                    'Le prix unitaire doit être un montant valide.'
                );

                return $this->render('admin/entree_stock/new.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                    'magasins' => $magasins,
                    'stocksParProduitMagasin' => $stocksParProduitMagasin,
                ]);
            }

            $prixUnitaire = number_format(
                (float) $prixUnitaireInput,
                2,
                '.',
                ''
            );

            // DATE
            $dateEntree = new \DateTimeImmutable();
            $dateInput = trim(
                (string) $request->request->get('date_entree')
            );

            if ($dateInput !== '') {
                $date = \DateTimeImmutable::createFromFormat(
                    'Y-m-d\TH:i',
                    $dateInput
                );

                if ($date === false) {
                    $date = \DateTimeImmutable::createFromFormat(
                        'Y-m-d',
                        $dateInput
                    );
                }

                if ($date === false) {
                    $this->addFlash(
                        'danger',
                        'La date d’entrée n’est pas valide.'
                    );

                    return $this->render('admin/entree_stock/new.html.twig', [
                        'entree' => $entree,
                        'produits' => $produits,
                        'fournisseurs' => $fournisseurs,
                        'magasins' => $magasins,
                        'stocksParProduitMagasin' => $stocksParProduitMagasin,
                    ]);
                }

                $dateEntree = $date;
            }

            // AUTRES INFORMATIONS
            $referenceDocument = trim(
                (string) $request->request->get('reference_document')
            );

            $commentaire = trim(
                (string) $request->request->get('commentaire')
            );

            // UTILISATEUR CONNECTÉ
            $user = $this->getUser();

            // STOCK DU MAGASIN
            $stock = $stockMagasinRepository->findOneByProduitEtMagasin(
                $produit,
                $magasin
            );

            // Une entrée peut initialiser le stock du couple produit-magasin.
            if (!$stock instanceof StockMagasin) {
                $stock = new StockMagasin();
                $stock->setProduit($produit);
                $stock->setMagasin($magasin);
                $stock->setQuantite(0);

                $entityManager->persist($stock);
            }

            // CRÉATION DE L'ENTRÉE
            $entree->setProduit($produit);
            $entree->setMagasin($magasin);
            $entree->setFournisseur($fournisseur);
            $entree->setQuantite($quantite);
            $entree->setPrixUnitaire($prixUnitaire);
            $entree->setDateEntree($dateEntree);
            $entree->setReferenceDocument(
                $referenceDocument !== '' ? $referenceDocument : null
            );
            $entree->setCommentaire(
                $commentaire !== '' ? $commentaire : null
            );

            if ($user instanceof User) {
                $entree->setUtilisateur($user);
            }

            // AUGMENTATION DU STOCK DU MAGASIN
            $connection = $entityManager->getConnection();
            $connection->beginTransaction();

            try {
                $stock->setQuantite(
                    $stock->getQuantite() + $quantite
                );

                $entityManager->persist($entree);
                $entityManager->flush();

                $connection->commit();
            } catch (\Throwable $exception) {
                $connection->rollBack();
                throw $exception;
            }

            $this->addFlash(
                'success',
                'L’entrée a été enregistrée et le stock du magasin a été augmenté.'
            );

            return $this->redirectToRoute('app_entree_stock_index');
        }

        return $this->render('admin/entree_stock/new.html.twig', [
            'entree' => $entree,
            'produits' => $produits,
            'fournisseurs' => $fournisseurs,
            'magasins' => $magasins,
            'stocksParProduitMagasin' => $stocksParProduitMagasin,
        ]);
    }

    /**
     * Modifier une entrée de stock
     */
    #[Route(
        '/{id}/modifier',
        name: 'app_entree_stock_edit',
        methods: ['GET', 'POST']
    )]
    public function edit(
        EntreeStock $entree,
        Request $request,
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository,
        FournisseurRepository $fournisseurRepository,
        MagasinRepository $magasinRepository,
        StockMagasinRepository $stockMagasinRepository
    ): Response {
        // On conserve les anciens choix même s'ils sont devenus inactifs.
        $produits = $produitRepository->findBy([], ['nom' => 'ASC']);

        $fournisseurs = $fournisseurRepository->findBy(
            [],
            ['nom' => 'ASC']
        );

        $magasins = $magasinRepository->findBy(
            ['actif' => true],
            ['nom' => 'ASC']
        );

        $ancienMagasin = $entree->getMagasin();

        if (
            $ancienMagasin !== null
            && !in_array($ancienMagasin, $magasins, true)
        ) {
            $magasins[] = $ancienMagasin;
        }

        $stocksParProduitMagasin = $this->construireCarteDesStocks(
            $stockMagasinRepository
        );

        $ancienneQuantite = $entree->getQuantite();
        $ancienProduit = $entree->getProduit();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'modifier_entree_stock_' . $entree->getId(),
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Jeton CSRF invalide.'
                );
            }

            // PRODUIT
            $produitId = (int) $request->request->get('produit_id');
            $produit = $produitRepository->find($produitId);

            if (!$produit instanceof Produit) {
                $this->addFlash(
                    'danger',
                    'Le produit sélectionné est introuvable.'
                );

                return $this->render('admin/entree_stock/edit.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                    'magasins' => $magasins,
                    'stocksParProduitMagasin' => $stocksParProduitMagasin,
                ]);
            }

            // MAGASIN
            $magasinId = (int) $request->request->get('magasin_id');
            $magasin = $magasinRepository->find($magasinId);

            if (!$magasin instanceof Magasin) {
                $this->addFlash(
                    'danger',
                    'Le magasin sélectionné est introuvable.'
                );

                return $this->render('admin/entree_stock/edit.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                    'magasins' => $magasins,
                    'stocksParProduitMagasin' => $stocksParProduitMagasin,
                ]);
            }

            // FOURNISSEUR
            $fournisseur = null;
            $fournisseurId = (int) $request->request->get('fournisseur_id');

            if ($fournisseurId > 0) {
                $fournisseur = $fournisseurRepository->find($fournisseurId);

                if (!$fournisseur) {
                    $this->addFlash(
                        'danger',
                        'Le fournisseur sélectionné est introuvable.'
                    );

                    return $this->render('admin/entree_stock/edit.html.twig', [
                        'entree' => $entree,
                        'produits' => $produits,
                        'fournisseurs' => $fournisseurs,
                        'magasins' => $magasins,
                        'stocksParProduitMagasin' => $stocksParProduitMagasin,
                    ]);
                }
            }

            // QUANTITÉ
            $quantite = (int) $request->request->get('quantite');

            if ($quantite <= 0) {
                $this->addFlash(
                    'danger',
                    'La quantité doit être supérieure à zéro.'
                );

                return $this->render('admin/entree_stock/edit.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                    'magasins' => $magasins,
                    'stocksParProduitMagasin' => $stocksParProduitMagasin,
                ]);
            }

            // PRIX
            $prixUnitaireInput = str_replace(
                ',',
                '.',
                trim((string) $request->request->get('prix_unitaire'))
            );

            if (
                $prixUnitaireInput === ''
                || !is_numeric($prixUnitaireInput)
                || (float) $prixUnitaireInput < 0
            ) {
                $this->addFlash(
                    'danger',
                    'Le prix unitaire doit être un montant valide.'
                );

                return $this->render('admin/entree_stock/edit.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                    'magasins' => $magasins,
                    'stocksParProduitMagasin' => $stocksParProduitMagasin,
                ]);
            }

            $prixUnitaire = number_format(
                (float) $prixUnitaireInput,
                2,
                '.',
                ''
            );

            // DATE
            $dateEntree = $entree->getDateEntree()
                ?? new \DateTimeImmutable();

            $dateInput = trim(
                (string) $request->request->get('date_entree')
            );

            if ($dateInput !== '') {
                $date = \DateTimeImmutable::createFromFormat(
                    'Y-m-d\TH:i',
                    $dateInput
                );

                if ($date === false) {
                    $date = \DateTimeImmutable::createFromFormat(
                        'Y-m-d',
                        $dateInput
                    );
                }

                if ($date === false) {
                    $this->addFlash(
                        'danger',
                        'La date d’entrée n’est pas valide.'
                    );

                    return $this->render('admin/entree_stock/edit.html.twig', [
                        'entree' => $entree,
                        'produits' => $produits,
                        'fournisseurs' => $fournisseurs,
                        'magasins' => $magasins,
                        'stocksParProduitMagasin' => $stocksParProduitMagasin,
                    ]);
                }

                $dateEntree = $date;
            }

            // AUTRES INFORMATIONS
            $referenceDocument = trim(
                (string) $request->request->get('reference_document')
            );

            $commentaire = trim(
                (string) $request->request->get('commentaire')
            );

            if ($ancienProduit === null || $ancienMagasin === null) {
                throw $this->createNotFoundException(
                    'Cette entrée ne possède pas de produit ou de magasin associé.'
                );
            }

            // RÉCUPÉRER L'ANCIEN STOCK
            $ancienStock = $stockMagasinRepository->findOneByProduitEtMagasin(
                $ancienProduit,
                $ancienMagasin
            );

            if (!$ancienStock instanceof StockMagasin) {
                $this->addFlash(
                    'danger',
                    'Le stock d’origine est introuvable. La modification est impossible.'
                );

                return $this->render('admin/entree_stock/edit.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                    'magasins' => $magasins,
                    'stocksParProduitMagasin' => $stocksParProduitMagasin,
                ]);
            }

            // RÉCUPÉRER OU INITIALISER LE NOUVEAU STOCK
            $nouveauStock = $stockMagasinRepository->findOneByProduitEtMagasin(
                $produit,
                $magasin
            );

            $memeCouple =
                $ancienProduit->getId() === $produit->getId()
                && $ancienMagasin->getId() === $magasin->getId();

            if (!$memeCouple && !$nouveauStock instanceof StockMagasin) {
                $nouveauStock = new StockMagasin();
                $nouveauStock->setProduit($produit);
                $nouveauStock->setMagasin($magasin);
                $nouveauStock->setQuantite(0);
                $entityManager->persist($nouveauStock);
            }

            // CALCULER LES STOCKS AVANT DE LES MODIFIER
            if ($memeCouple) {
                $stockCalcule = $ancienStock->getQuantite()
                    - $ancienneQuantite
                    + $quantite;

                if ($stockCalcule < 0) {
                    $this->addFlash(
                        'danger',
                        'La modification entraînerait un stock négatif.'
                    );

                    return $this->render('admin/entree_stock/edit.html.twig', [
                        'entree' => $entree,
                        'produits' => $produits,
                        'fournisseurs' => $fournisseurs,
                        'magasins' => $magasins,
                        'stocksParProduitMagasin' => $stocksParProduitMagasin,
                    ]);
                }
            } else {
                $stockAncienCalcule = $ancienStock->getQuantite()
                    - $ancienneQuantite;

                if ($stockAncienCalcule < 0) {
                    $this->addFlash(
                        'danger',
                        'Impossible de déplacer cette entrée : le stock d’origine deviendrait négatif.'
                    );

                    return $this->render('admin/entree_stock/edit.html.twig', [
                        'entree' => $entree,
                        'produits' => $produits,
                        'fournisseurs' => $fournisseurs,
                        'magasins' => $magasins,
                        'stocksParProduitMagasin' => $stocksParProduitMagasin,
                    ]);
                }
            }

            // TRANSACTION : STOCKS + ENTRÉE
            $connection = $entityManager->getConnection();
            $connection->beginTransaction();

            try {
                if ($memeCouple) {
                    $ancienStock->setQuantite($stockCalcule);
                } else {
                    $ancienStock->setQuantite($stockAncienCalcule);

                    $nouveauStock->setQuantite(
                        $nouveauStock->getQuantite() + $quantite
                    );
                }

                // MISE À JOUR DE L'ENTRÉE
                $entree->setProduit($produit);
                $entree->setMagasin($magasin);
                $entree->setFournisseur($fournisseur);
                $entree->setQuantite($quantite);
                $entree->setPrixUnitaire($prixUnitaire);
                $entree->setDateEntree($dateEntree);
                $entree->setReferenceDocument(
                    $referenceDocument !== '' ? $referenceDocument : null
                );
                $entree->setCommentaire(
                    $commentaire !== '' ? $commentaire : null
                );

                $entityManager->flush();
                $connection->commit();
            } catch (\Throwable $exception) {
                $connection->rollBack();
                throw $exception;
            }

            $this->addFlash(
                'success',
                'L’entrée a été modifiée et les stocks des magasins ont été recalculés.'
            );

            return $this->redirectToRoute('app_entree_stock_index');
        }

        return $this->render('admin/entree_stock/edit.html.twig', [
            'entree' => $entree,
            'produits' => $produits,
            'fournisseurs' => $fournisseurs,
            'magasins' => $magasins,
            'stocksParProduitMagasin' => $stocksParProduitMagasin,
        ]);
    }

    /**
     * Construire une carte des quantités par produit et magasin.
     *
     * Format : "produitId-magasinId" => quantité
     */
    private function construireCarteDesStocks(
        StockMagasinRepository $stockMagasinRepository
    ): array {
        $carte = [];

        foreach ($stockMagasinRepository->findAll() as $stock) {
            $produitId = $stock->getProduit()?->getId();
            $magasinId = $stock->getMagasin()?->getId();

            if ($produitId !== null && $magasinId !== null) {
                $cle = $produitId . '-' . $magasinId;
                $carte[$cle] = $stock->getQuantite();
            }
        }

        return $carte;
    }
}
