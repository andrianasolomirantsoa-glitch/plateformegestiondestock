<?php

namespace App\Entity;

use App\Repository\LigneInventaireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneInventaireRepository::class)]
#[ORM\Table(name: 'lignes_inventaire')]
#[ORM\UniqueConstraint(
    name: 'uniq_inventaire_produit',
    columns: ['inventaire_id', 'produit_id']
)]
class LigneInventaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(
        targetEntity: Inventaire::class,
        inversedBy: 'lignes'
    )]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Inventaire $inventaire = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Produit $produit = null;

    #[ORM\Column]
    private int $stockTheorique = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
private ?int $quantitePhysique = null;

#[ORM\Column(type: 'integer', nullable: true)]
private ?int $ecart = null;

#[ORM\Column(type: 'text', nullable: true)]
private ?string $observation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInventaire(): ?Inventaire
    {
        return $this->inventaire;
    }

    public function setInventaire(Inventaire $inventaire): static
    {
        $this->inventaire = $inventaire;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(Produit $produit): static
    {
        $this->produit = $produit;
        return $this;
    }

    public function getStockTheorique(): int
    {
        return $this->stockTheorique;
    }

    public function setStockTheorique(int $stockTheorique): static
    {
        $this->stockTheorique = $stockTheorique;
        $this->calculerEcart();

        return $this;
    }

    public function getQuantitePhysique(): ?int
    {
        return $this->quantitePhysique;
    }

    public function setQuantitePhysique(?int $quantitePhysique): static
    {
        $this->quantitePhysique = $quantitePhysique;
        $this->calculerEcart();

        return $this;
    }

    public function getEcart(): ?int
    {
        return $this->ecart;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;
        return $this;
    }

    private function calculerEcart(): void
    {
        $this->ecart = $this->quantitePhysique === null
            ? null
            : $this->quantitePhysique - $this->stockTheorique;
    }
}
