<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/produits')]
#[IsGranted('ROLE_CLIENT')]
class ClientProduitController extends AbstractController
{
    #[Route('', name: 'app_client_produit_index', methods: ['GET'])]
    public function index(
        ProduitRepository $produitRepository
    ): Response {
        $produits = $produitRepository->findBy(
            ['actif' => true],
            ['createdAt' => 'DESC']
        );

        return $this->render('client/produit/index.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/{id}', name: 'app_client_produit_show', methods: ['GET'])]
    public function show(
        \App\Entity\Produit $produit
    ): Response {
        if (!$produit->isActif()) {
            throw $this->createNotFoundException(
                'Ce produit n’est pas disponible.'
            );
        }

        return $this->render('client/produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }
}