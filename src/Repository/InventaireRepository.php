<?php

namespace App\Repository;

use App\Entity\Inventaire;
use App\Entity\Magasin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inventaire>
 */
class InventaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inventaire::class);
    }

    /**
     * Liste les inventaires d'un magasin.
     *
     * @return Inventaire[]
     */
    public function findByMagasin(Magasin $magasin): array
    {
        return $this->findBy(
            ['magasin' => $magasin],
            ['dateInventaire' => 'DESC']
        );
    }

    /**
     * Liste les inventaires les plus récents.
     *
     * @return Inventaire[]
     */
    public function findRecents(int $limit = 20): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.dateInventaire', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
