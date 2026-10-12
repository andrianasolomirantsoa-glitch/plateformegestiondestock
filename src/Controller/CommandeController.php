<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\User;
use App\Repository\CommandeRepository;
use App\Repository\LigneCommandeRepository;
use App\Repository\LivraisonRepository;
use App\Repository\PaiementRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/commande')]
#[IsGranted('ROLE_CLIENT')]
class CommandeController extends AbstractController
{
    #[Route(
        '',
        name: 'app_client_commandes',
        methods: ['GET']
    )]
    public function index(
        CommandeRepository $commandeRepository,
        PaiementRepository $paiementRepository,
        LivraisonRepository $livraisonRepository
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $commandes = $commandeRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        $paiements = [];
        $livraisons = [];

        foreach ($commandes as $commande) {
            $paiements[$commande->getId()] =
                $paiementRepository->findOneBy([
                    'commande' => $commande,
                ]);

            $livraisons[$commande->getId()] =
                $livraisonRepository->findOneBy([
                    'commande' => $commande,
                ]);
        }

        return $this->render(
            'client/commande/index.html.twig',
            [
                'commandes' => $commandes,
                'paiements' => $paiements,
                'livraisons' => $livraisons,
            ]
        );
    }

    #[Route(
        '/valider',
        name: 'app_client_commande_create',
        methods: ['POST']
    )]
    public function create(
        Request $request,
        CartService $cartService,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid(
            'passer_commande',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException(
                'Utilisateur non valide.'
            );
        }

        $items = $cartService->getItems();

        if (empty($items)) {
            $this->addFlash(
                'warning',
                'Votre panier est vide.'
            );

            return $this->redirectToRoute(
                'app_client_cart'
            );
        }

        /*
         * Vérification finale du stock.
         */
        foreach ($items as $item) {
            $produit = $item['produit'];
            $quantite = $item['quantite'];

            if (!$produit->isActif()) {
                $this->addFlash(
                    'danger',
                    'Le produit "' .
                    $produit->getNom() .
                    '" n’est plus disponible.'
                );

                return $this->redirectToRoute(
                    'app_client_cart'
                );
            }

            if ($quantite > $produit->getQuantiteStock()) {
                $this->addFlash(
                    'danger',
                    'Stock insuffisant pour le produit "' .
                    $produit->getNom() .
                    '". Stock disponible : ' .
                    $produit->getQuantiteStock()
                );

                return $this->redirectToRoute(
                    'app_client_cart'
                );
            }
        }

        $connection = $entityManager->getConnection();

        try {
            $connection->beginTransaction();

            /*
             * Génération du numéro de commande.
             */
            do {
                $numero = sprintf(
                    'CMD-%s-%04d',
                    date('YmdHis'),
                    random_int(0, 9999)
                );

                $commandeExistante = $entityManager
                    ->getRepository(Commande::class)
                    ->findOneBy([
                        'numero' => $numero,
                    ]);
            } while ($commandeExistante !== null);

            /*
             * Création de la commande.
             */
            $commande = new Commande();

            $commande->setNumero($numero);
            $commande->setStatut('EN_ATTENTE');
            $commande->setTotal('0.00');
            $commande->setUser($user);

            $total = 0.0;

            /*
             * Création des lignes.
             */
            foreach ($items as $item) {
                $produit = $item['produit'];
                $quantite = $item['quantite'];

                $prixUnitaire = (float) $produit->getPrixVente();
                $sousTotal = $prixUnitaire * $quantite;

                $ligne = new LigneCommande();

                $ligne->setCommande($commande);
                $ligne->setProduit($produit);
                $ligne->setQuantite($quantite);

                $ligne->setPrixUnitaire(
                    number_format(
                        $prixUnitaire,
                        2,
                        '.',
                        ''
                    )
                );

                $ligne->setSousTotal(
                    number_format(
                        $sousTotal,
                        2,
                        '.',
                        ''
                    )
                );

                /*
                 * Diminution du stock.
                 */
                $produit->setQuantiteStock(
                    $produit->getQuantiteStock() - $quantite
                );

                $produit->setUpdatedAt(
                    new \DateTimeImmutable()
                );

                $entityManager->persist($ligne);

                $total += $sousTotal;
            }

            /*
             * Total de la commande.
             */
            $commande->setTotal(
                number_format(
                    $total,
                    2,
                    '.',
                    ''
                )
            );

            $commande->setUpdatedAt(
                new \DateTimeImmutable()
            );

            $entityManager->persist($commande);

            $entityManager->flush();

            $connection->commit();

            /*
             * Vider le panier après succès.
             */
            $cartService->clear();

            $this->addFlash(
                'success',
                'Votre commande ' .
                $commande->getNumero() .
                ' a été enregistrée avec succès.'
            );

            return $this->redirectToRoute(
                'app_client_commande_confirmation',
                [
                    'id' => $commande->getId(),
                ]
            );

        } catch (\Throwable $e) {
            $connection->rollBack();

            throw $e;
        }
    }

    #[Route(
        '/confirmation/{id}',
        name: 'app_client_commande_confirmation',
        methods: ['GET']
    )]
    public function confirmation(
        Commande $commande,
        LigneCommandeRepository $ligneCommandeRepository,
        PaiementRepository $paiementRepository,
        LivraisonRepository $livraisonRepository
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        /*
         * Le client ne peut voir que ses propres commandes.
         */
        if (
            $commande->getUser() === null ||
            $commande->getUser()->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas consulter cette commande.'
            );
        }

        $lignes = $ligneCommandeRepository->findBy(
            [
                'commande' => $commande,
            ]
        );

        $paiement = $paiementRepository->findOneBy(
            [
                'commande' => $commande,
            ]
        );

        $livraison = $livraisonRepository->findOneBy(
            [
                'commande' => $commande,
            ]
        );

        return $this->render(
            'client/commande/confirmation.html.twig',
            [
                'commande' => $commande,
                'lignes' => $lignes,
                'paiement' => $paiement,
                'livraison' => $livraison,
            ]
        );
    }
}

