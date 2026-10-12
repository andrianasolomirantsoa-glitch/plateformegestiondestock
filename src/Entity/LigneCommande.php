<?php

namespace App\Entity;

use App\Repository\LigneCommandeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneCommandeRepository::class)]
#[ORM\Table(name: 'ligne_commandes')]
class LigneCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(
        name: 'commande_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(
        name: 'produit_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Produit $produit = null;

    #[ORM\Column]
    private int $quantite = 1;

    #[ORM\Column(name: 'prix_unitaire', type: 'decimal', precision: 12, scale: 2)]
    private ?string $prixUnitaire = null;

    #[ORM\Column(name: 'sous_total', type: 'decimal', precision: 12, scale: 2)]
    private ?string $sousTotal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;

        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;

        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrixUnitaire(): ?string
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(string $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
    }

    public function getSousTotal(): ?string
    {
        return $this->sousTotal;
    }

    public function setSousTotal(string $sousTotal): static
    {
        $this->sousTotal = $sousTotal;

        return $this;
    }
}

