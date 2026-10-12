<?php

namespace App\Controller;

use App\Entity\EntreeStock;
use App\Entity\Produit;
use App\Entity\User;
use App\Repository\EntreeStockRepository;
use App\Repository\FournisseurRepository;
use App\Repository\ProduitRepository;
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

                        return str_contains($produitNom, $needle)
                            || str_contains($produitReference, $needle)
                            || str_contains($fournisseurNom, $needle)
                            || str_contains($referenceDocument, $needle);
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
        FournisseurRepository $fournisseurRepository
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

        if ($request->isMethod('POST')) {

            if (!$this->isCsrfTokenValid(
                'creer_entree_stock',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Jeton CSRF invalide.'
                );
            }


            /* =====================================================
               PRODUIT
               ===================================================== */

            $produitId = (int) $request->request->get('produit_id');

            if ($produitId <= 0) {

                $this->addFlash(
                    'danger',
                    'Veuillez sélectionner un produit.'
                );

                return $this->render('admin/entree_stock/new.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                ]);
            }

            $produit = $produitRepository->find($produitId);

            if (!$produit instanceof Produit) {

                $this->addFlash(
                    'danger',
                    'Le produit sélectionné est introuvable.'
                );

                return $this->render('admin/entree_stock/new.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                ]);
            }

            if (!$produit->isActif()) {

                $this->addFlash(
                    'danger',
                    'Le produit sélectionné est inactif.'
                );

                return $this->render('admin/entree_stock/new.html.twig', [
                    'entree' => $entree,
                    'produits' => $produits,
                    'fournisseurs' => $fournisseurs,
                ]);
            }


            /* =====================================================
               FOURNISSEUR
               ===================================================== */

            $fournisseur = null;

            $fournisseurId = (int) $request->request->get('fournisseur_id');

            if ($fournisseurId > 0) {

                $fournisseur = $fournisseurRepository->find(
                    $fournisseurId
                );

                if (!$fournisseur) {

                    $this->addFlash(
                        'danger',
                        'Le fournisseur sélectionné est introuvable.'
                    );

                    return $this->render(
                        'admin/entree_stock/new.html.twig',
                        [
                            'entree' => $entree,
                            'produits' => $produits,
                            'fournisseurs' => $fournisseurs,
                        ]
                    );
                }

                if (!$fournisseur->isActif()) {

                    $this->addFlash(
                        'danger',
                        'Le fournisseur sélectionné est inactif.'
                    );

                    return $this->render(
                        'admin/entree_stock/new.html.twig',
                        [
                            'entree' => $entree,
                            'produits' => $produits,
                            'fournisseurs' => $fournisseurs,
                        ]
                    );
                }
            }


            /* =====================================================
               QUANTITE
               ===================================================== */

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
                ]);
            }


            /* =====================================================
               PRIX UNITAIRE
               ===================================================== */

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
                ]);
            }

            $prixUnitaire = number_format(
                (float) $prixUnitaireInput,
                2,
                '.',
                ''
            );


            /* =====================================================
               DATE
               ===================================================== */

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

                    return $this->render(
                        'admin/entree_stock/new.html.twig',
                        [
                            'entree' => $entree,
                            'produits' => $produits,
                            'fournisseurs' => $fournisseurs,
                        ]
                    );
                }

                $dateEntree = $date;
            }


            /* =====================================================
               AUTRES INFORMATIONS
               ===================================================== */

            $referenceDocument = trim(
                (string) $request->request->get('reference_document')
            );

            $commentaire = trim(
                (string) $request->request->get('commentaire')
            );


            /* =====================================================
               UTILISATEUR CONNECTE
               ===================================================== */

            $user = $this->getUser();

            if ($user instanceof User) {
                $entree->setUtilisateur($user);
            }


            /* =====================================================
               CREATION
               ===================================================== */

            $entree->setProduit($produit);
            $entree->setFournisseur($fournisseur);
            $entree->setQuantite($quantite);
            $entree->setPrixUnitaire($prixUnitaire);
            $entree->setDateEntree($dateEntree);
            $entree->setReferenceDocument(
                $referenceDocument !== ''
                    ? $referenceDocument
                    : null
            );
            $entree->setCommentaire(
                $commentaire !== ''
                    ? $commentaire
                    : null
            );


            /* =====================================================
               MISE A JOUR AUTOMATIQUE DU STOCK
               ===================================================== */

            $stockActuel = $produit->getQuantiteStock();

            $produit->setQuantiteStock(
                $stockActuel + $quantite
            );


            $entityManager->persist($entree);

            $entityManager->flush();


            $this->addFlash(
                'success',
                'L’entrée de stock a été enregistrée et le stock du produit a été mis à jour.'
            );

            return $this->redirectToRoute(
                'app_entree_stock_index'
            );
        }


        return $this->render(
            'admin/entree_stock/new.html.twig',
            [
                'entree' => $entree,
                'produits' => $produits,
                'fournisseurs' => $fournisseurs,
            ]
        );
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
        FournisseurRepository $fournisseurRepository
    ): Response {
        $produits = $produitRepository->findBy(
            ['actif' => true],
            ['nom' => 'ASC']
        );

        $fournisseurs = $fournisseurRepository->findBy(
            ['actif' => true],
            ['nom' => 'ASC']
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


            /* =====================================================
               PRODUIT
               ===================================================== */

            $produitId = (int) $request->request->get('produit_id');

            $produit = $produitRepository->find($produitId);

            if (!$produit instanceof Produit) {

                $this->addFlash(
                    'danger',
                    'Le produit sélectionné est introuvable.'
                );

                return $this->render(
                    'admin/entree_stock/edit.html.twig',
                    [
                        'entree' => $entree,
                        'produits' => $produits,
                        'fournisseurs' => $fournisseurs,
                    ]
                );
            }


            /* =====================================================
               FOURNISSEUR
               ===================================================== */

            $fournisseur = null;

            $fournisseurId = (int) $request->request->get('fournisseur_id');

            if ($fournisseurId > 0) {

                $fournisseur = $fournisseurRepository->find(
                    $fournisseurId
                );

                if (!$fournisseur) {

                    $this->addFlash(
                        'danger',
                        'Le fournisseur sélectionné est introuvable.'
                    );

                    return $this->render(
                        'admin/entree_stock/edit.html.twig',
                        [
                            'entree' => $entree,
                            'produits' => $produits,
                            'fournisseurs' => $fournisseurs,
                        ]
                    );
                }
            }


            /* =====================================================
               QUANTITE
               ===================================================== */

            $quantite = (int) $request->request->get('quantite');

            if ($quantite <= 0) {

                $this->addFlash(
                    'danger',
                    'La quantité doit être supérieure à zéro.'
                );

                return $this->render(
                    'admin/entree_stock/edit.html.twig',
                    [
                        'entree' => $entree,
                        'produits' => $produits,
                        'fournisseurs' => $fournisseurs,
                    ]
                );
            }


            /* =====================================================
               PRIX
               ===================================================== */

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

                return $this->render(
                    'admin/entree_stock/edit.html.twig',
                    [
                        'entree' => $entree,
                        'produits' => $produits,
                        'fournisseurs' => $fournisseurs,
                    ]
                );
            }

            $prixUnitaire = number_format(
                (float) $prixUnitaireInput,
                2,
                '.',
                ''
            );


            /* =====================================================
               DATE
               ===================================================== */

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

                    return $this->render(
                        'admin/entree_stock/edit.html.twig',
                        [
                            'entree' => $entree,
                            'produits' => $produits,
                            'fournisseurs' => $fournisseurs,
                        ]
                    );
                }

                $dateEntree = $date;
            }


            /* =====================================================
               AUTRES INFORMATIONS
               ===================================================== */

            $referenceDocument = trim(
                (string) $request->request->get('reference_document')
            );

            $commentaire = trim(
                (string) $request->request->get('commentaire')
            );


            /* =====================================================
               CORRECTION DU STOCK
               ===================================================== */

            if (
                $ancienProduit !== null
                && $ancienProduit->getId() === $produit->getId()
            ) {

                /*
                 * Exemple :
                 * ancienne entrée = 10
                 * nouvelle entrée = 15
                 * différence = +5
                 */

                $difference = $quantite - $ancienneQuantite;

                $nouveauStock =
                    $ancienProduit->getQuantiteStock()
                    + $difference;

                if ($nouveauStock < 0) {

                    $this->addFlash(
                        'danger',
                        'La modification entraînerait un stock négatif.'
                    );

                    return $this->render(
                        'admin/entree_stock/edit.html.twig',
                        [
                            'entree' => $entree,
                            'produits' => $produits,
                            'fournisseurs' => $fournisseurs,
                        ]
                    );
                }

                $ancienProduit->setQuantiteStock(
                    $nouveauStock
                );

            } else {

                /*
                 * Le produit a changé.
                 * On retire l'ancienne quantité de l'ancien produit
                 * puis on ajoute la nouvelle quantité au nouveau produit.
                 */

                if ($ancienProduit !== null) {

                    $ancienStock =
                        $ancienProduit->getQuantiteStock()
                        - $ancienneQuantite;

                    if ($ancienStock < 0) {

                        $this->addFlash(
                            'danger',
                            'Impossible de modifier cette entrée car le stock de l’ancien produit deviendrait négatif.'
                        );

                        return $this->render(
                            'admin/entree_stock/edit.html.twig',
                            [
                                'entree' => $entree,
                                'produits' => $produits,
                                'fournisseurs' => $fournisseurs,
                            ]
                        );
                    }

                    $ancienProduit->setQuantiteStock(
                        $ancienStock
                    );
                }

                $produit->setQuantiteStock(
                    $produit->getQuantiteStock()
                    + $quantite
                );
            }


            /* =====================================================
               MISE A JOUR DE L'ENTREE
               ===================================================== */

            $entree->setProduit($produit);
            $entree->setFournisseur($fournisseur);
            $entree->setQuantite($quantite);
            $entree->setPrixUnitaire($prixUnitaire);
            $entree->setDateEntree($dateEntree);
            $entree->setReferenceDocument(
                $referenceDocument !== ''
                    ? $referenceDocument
                    : null
            );
            $entree->setCommentaire(
                $commentaire !== ''
                    ? $commentaire
                    : null
            );


            $entityManager->flush();


            $this->addFlash(
                'success',
                'L’entrée de stock a été modifiée et le stock a été recalculé.'
            );

            return $this->redirectToRoute(
                'app_entree_stock_index'
            );
        }


        return $this->render(
            'admin/entree_stock/edit.html.twig',
            [
                'entree' => $entree,
                'produits' => $produits,
                'fournisseurs' => $fournisseurs,
            ]
        );
    }
}
