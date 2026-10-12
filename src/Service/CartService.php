<?php

namespace App\Service;

use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const CART_KEY = 'panier';

    public function __construct(
        private RequestStack $requestStack,
        private ProduitRepository $produitRepository
    ) {
    }

    /**
     * Récupère le panier stocké dans la session.
     */
    private function getCart(): array
    {
        return $this->requestStack
            ->getSession()
            ->get(self::CART_KEY, []);
    }

    /**
     * Sauvegarde le panier dans la session.
     */
    private function saveCart(array $cart): void
    {
        $this->requestStack
            ->getSession()
            ->set(self::CART_KEY, $cart);
    }

    /**
     * Ajouter un produit au panier.
     */
    public function add(int $produitId, int $quantite = 1): void
    {
        $produit = $this->produitRepository->find($produitId);

        if (!$produit || !$produit->isActif()) {
            return;
        }

        if ($produit->getQuantiteStock() <= 0) {
            return;
        }

        $cart = $this->getCart();

        if (isset($cart[$produitId])) {
            $cart[$produitId] += $quantite;
        } else {
            $cart[$produitId] = $quantite;
        }

        // Ne jamais dépasser le stock disponible.
        if ($cart[$produitId] > $produit->getQuantiteStock()) {
            $cart[$produitId] = $produit->getQuantiteStock();
        }

        $this->saveCart($cart);
    }

    /**
     * Augmenter la quantité.
     */
    public function increase(int $produitId): void
    {
        $this->add($produitId, 1);
    }

    /**
     * Diminuer la quantité.
     */
    public function decrease(int $produitId): void
    {
        $cart = $this->getCart();

        if (!isset($cart[$produitId])) {
            return;
        }

        $cart[$produitId]--;

        if ($cart[$produitId] <= 0) {
            unset($cart[$produitId]);
        }

        $this->saveCart($cart);
    }

    /**
     * Supprimer complètement un produit.
     */
    public function remove(int $produitId): void
    {
        $cart = $this->getCart();

        unset($cart[$produitId]);

        $this->saveCart($cart);
    }

    /**
     * Vider complètement le panier.
     */
    public function clear(): void
    {
        $this->saveCart([]);
    }

    /**
     * Retourne les produits du panier avec leurs quantités.
     */
    public function getItems(): array
    {
        $cart = $this->getCart();

        $items = [];

        foreach ($cart as $produitId => $quantite) {
            $produit = $this->produitRepository->find($produitId);

            if (!$produit || !$produit->isActif()) {
                continue;
            }

            $items[] = [
                'produit' => $produit,
                'quantite' => $quantite,
                'sous_total' => $produit->getPrixVente() * $quantite,
            ];
        }

        return $items;
    }

    /**
     * Nombre total d'articles dans le panier.
     */
    public function getTotalQuantity(): int
    {
        return array_sum($this->getCart());
    }

    /**
     * Montant total du panier.
     */
    public function getTotal(): float
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            $total += (float) $item['sous_total'];
        }

        return $total;
    }
}