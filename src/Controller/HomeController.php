<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProduitRepository $produitRepository): Response
    {
        // On récupère uniquement les produits actifs.
        // Les produits sont affichés du plus récent au plus ancien.
        $produits = $produitRepository->findBy(
            ['actif' => true],
            ['createdAt' => 'DESC']
        );

        // On limite volontairement la vitrine à 8 produits.
        // Le catalogue complet reste accessible depuis l'espace client.
        $produits = array_slice($produits, 0, 8);

        return $this->render('home.html.twig', [
            'produits' => $produits,
        ]);
    }
}

