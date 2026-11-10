<?php

namespace App\Repository;

use App\Entity\Magasin;
use App\Entity\Produit;
use App\Entity\StockMagasin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockMagasin>
 */
class StockMagasinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockMagasin::class);
    }

    /**
     * Récupérer le stock d'un produit dans un magasin précis.
     */
    public function findOneByProduitEtMagasin(
        Produit $produit,
        Magasin $magasin
    ): ?StockMagasin {
        return $this->findOneBy([
            'produit' => $produit,
            'magasin' => $magasin,
        ]);
    }

    /**
     * Récupérer tous les stocks d'un magasin.
     *
     * @return StockMagasin[]
     */
    public function findByMagasin(Magasin $magasin): array
    {
        return $this->findBy(
            ['magasin' => $magasin],
            ['id' => 'DESC']
        );
    }

    /**
     * Récupérer tous les stocks d'un produit.
     *
     * @return StockMagasin[]
     */
    public function findByProduit(Produit $produit): array
    {
        return $this->findBy(
            ['produit' => $produit],
            ['id' => 'DESC']
        );
    }
}
