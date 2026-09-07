<?php

namespace App\Entity;

use App\Repository\EntreeStockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntreeStockRepository::class)]
#[ORM\Table(name: 'entrees_stock')]
class EntreeStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'produit_id', nullable: false)]
    private ?Produit $produit = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'fournisseur_id', nullable: true)]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'utilisateur_id', nullable: true)]
    private ?User $utilisateur = null;

    #[ORM\Column]
    private int $quantite = 0;

    #[ORM\Column(name: 'prix_unitaire', type: 'decimal', precision: 12, scale: 2)]
    private ?string $prixUnitaire = null;

    #[ORM\Column(name: 'date_entree')]
    private ?\DateTimeImmutable $dateEntree = null;

    #[ORM\Column(name: 'reference_document', length: 100, nullable: true)]
    private ?string $referenceDocument = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    public function __construct()
    {
        $this->dateEntree = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

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

    public function getDateEntree(): ?\DateTimeImmutable
    {
        return $this->dateEntree;
    }

    public function setDateEntree(\DateTimeImmutable $dateEntree): static
    {
        $this->dateEntree = $dateEntree;

        return $this;
    }

    public function getReferenceDocument(): ?string
    {
        return $this->referenceDocument;
    }

    public function setReferenceDocument(?string $referenceDocument): static
    {
        $this->referenceDocument = $referenceDocument;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }
}

