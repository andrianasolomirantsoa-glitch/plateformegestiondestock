<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Repository\LigneCommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/commandes')]
#[IsGranted('ROLE_ADMIN')]
class AdminCommandeController extends AbstractController
{
    #[Route('', name: 'app_admin_commande_index', methods: ['GET'])]
    public function index(
        CommandeRepository $commandeRepository
    ): Response {
        $commandes = $commandeRepository->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        return $this->render(
            'admin/commande/index.html.twig',
            [
                'commandes' => $commandes,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_admin_commande_show',
        methods: ['GET']
    )]
    public function show(
        Commande $commande,
        LigneCommandeRepository $ligneCommandeRepository
    ): Response {
        $lignes = $ligneCommandeRepository->findBy(
            ['commande' => $commande]
        );

        return $this->render(
            'admin/commande/show.html.twig',
            [
                'commande' => $commande,
                'lignes' => $lignes,
            ]
        );
    }

    #[Route(
        '/{id}/statut/{statut}',
        name: 'app_admin_commande_status',
        methods: ['POST']
    )]
    public function changeStatus(
        Commande $commande,
        string $statut,
        LigneCommandeRepository $ligneCommandeRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if (!$this->isCsrfTokenValid(
            'commande_statut_' . $commande->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $statutsAutorises = [
            'CONFIRMEE',
            'ANNULEE',
        ];

        if (!in_array($statut, $statutsAutorises, true)) {
            $this->addFlash(
                'danger',
                'Statut de commande invalide.'
            );

            return $this->redirectToRoute(
                'app_admin_commande_show',
                ['id' => $commande->getId()]
            );
        }

        if ($commande->getStatut() !== 'EN_ATTENTE') {
            $this->addFlash(
                'warning',
                'Cette commande ne peut plus être modifiée.'
            );

            return $this->redirectToRoute(
                'app_admin_commande_show',
                ['id' => $commande->getId()]
            );
        }

        $connection = $entityManager->getConnection();

        try {
            $connection->beginTransaction();

            if ($statut === 'ANNULEE') {
                $lignes = $ligneCommandeRepository->findBy(
                    ['commande' => $commande]
                );

                foreach ($lignes as $ligne) {
                    $produit = $ligne->getProduit();

                    if ($produit === null) {
                        continue;
                    }

                    $nouveauStock =
                        $produit->getQuantiteStock()
                        + $ligne->getQuantite();

                    $produit->setQuantiteStock($nouveauStock);

                    $produit->setUpdatedAt(
                        new \DateTimeImmutable()
                    );
                }
            }

            $commande->setStatut($statut);

            $commande->setUpdatedAt(
                new \DateTimeImmutable()
            );

            $entityManager->flush();

            $connection->commit();

            if ($statut === 'CONFIRMEE') {
                $this->addFlash(
                    'success',
                    'La commande ' .
                    $commande->getNumero() .
                    ' a été confirmée.'
                );
            } else {
                $this->addFlash(
                    'success',
                    'La commande ' .
                    $commande->getNumero() .
                    ' a été annulée et le stock a été rétabli.'
                );
            }

        } catch (\Throwable $e) {
            $connection->rollBack();

            throw $e;
        }

        return $this->redirectToRoute(
            'app_admin_commande_show',
            ['id' => $commande->getId()]
        );
    }
}

