<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/panier')]
#[IsGranted('ROLE_CLIENT')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_client_cart', methods: ['GET'])]
    public function index(CartService $cartService): Response
    {
        return $this->render('client/cart/index.html.twig', [
            'items' => $cartService->getItems(),
            'total' => $cartService->getTotal(),
            'totalQuantity' => $cartService->getTotalQuantity(),
        ]);
    }

    #[Route(
        '/ajouter/{id}',
        name: 'app_client_cart_add',
        methods: ['POST']
    )]
    public function add(
        int $id,
        CartService $cartService
    ): Response {
        $cartService->add($id);

        $this->addFlash(
            'success',
            'Le produit a été ajouté au panier.'
        );

        return $this->redirectToRoute(
            'app_client_produit_show',
            ['id' => $id]
        );
    }

    #[Route(
        '/augmenter/{id}',
        name: 'app_client_cart_increase',
        methods: ['POST']
    )]
    public function increase(
        int $id,
        CartService $cartService
    ): Response {
        $cartService->increase($id);

        return $this->redirectToRoute('app_client_cart');
    }

    #[Route(
        '/diminuer/{id}',
        name: 'app_client_cart_decrease',
        methods: ['POST']
    )]
    public function decrease(
        int $id,
        CartService $cartService
    ): Response {
        $cartService->decrease($id);

        return $this->redirectToRoute('app_client_cart');
    }

    #[Route(
        '/supprimer/{id}',
        name: 'app_client_cart_remove',
        methods: ['POST']
    )]
    public function remove(
        int $id,
        CartService $cartService
    ): Response {
        $cartService->remove($id);

        $this->addFlash(
            'success',
            'Le produit a été supprimé du panier.'
        );

        return $this->redirectToRoute('app_client_cart');
    }

    #[Route(
        '/vider',
        name: 'app_client_cart_clear',
        methods: ['POST']
    )]
    public function clear(
        CartService $cartService
    ): Response {
        $cartService->clear();

        $this->addFlash(
            'success',
            'Le panier a été vidé.'
        );

        return $this->redirectToRoute('app_client_cart');
    }
}