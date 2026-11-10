<?php

namespace App\Repository;

use App\Entity\Inventaire;
use App\Entity\LigneInventaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LigneInventaire>
 */
class LigneInventaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneInventaire::class);
    }

    /**
     * Liste les lignes d'un inventaire.
     *
     * @return LigneInventaire[]
     */
    public function findByInventaire(Inventaire $inventaire): array
    {
        return $this->findBy(
            ['inventaire' => $inventaire],
            ['id' => 'ASC']
        );
    }

    /**
     * Vérifie si toutes les lignes ont été comptées.
     */
    public function toutesLesLignesSontComptees(Inventaire $inventaire): bool
    {
        $nonComptees = $this->count([
            'inventaire' => $inventaire,
            'quantitePhysique' => null,
        ]);

        return $nonComptees === 0;
    }
}
