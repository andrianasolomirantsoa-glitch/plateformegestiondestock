<?php

namespace App\Entity;

use App\Repository\LivraisonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivraisonRepository::class)]
#[ORM\Table(name: 'livraisons')]
class Livraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(
        name: 'commande_id',
        referencedColumnName: 'id',
        nullable: false,
        unique: true,
        onDelete: 'CASCADE'
    )]
    private ?Commande $commande = null;

    #[ORM\Column(name: 'mode_livraison', length: 30)]
    private string $modeLivraison = 'DOMICILE';

    #[ORM\Column(length: 30)]
    private string $statut = 'EN_ATTENTE';

    #[ORM\Column(name: 'adresse_livraison', length: 255)]
    private ?string $adresseLivraison = null;

    #[ORM\Column(length: 100)]
    private ?string $ville = null;

    #[ORM\Column(length: 100)]
    private string $pays = 'Madagascar';

    #[ORM\Column(name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'date_livraison', nullable: true)]
    private ?\DateTimeImmutable $dateLivraison = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getModeLivraison(): string
    {
        return $this->modeLivraison;
    }

    public function setModeLivraison(string $modeLivraison): static
    {
        $this->modeLivraison = $modeLivraison;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getAdresseLivraison(): ?string
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(string $adresseLivraison): static
    {
        $this->adresseLivraison = $adresseLivraison;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getPays(): string
    {
        return $this->pays;
    }

    public function setPays(string $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDateLivraison(): ?\DateTimeImmutable
    {
        return $this->dateLivraison;
    }

    public function setDateLivraison(?\DateTimeImmutable $dateLivraison): static
    {
        $this->dateLivraison = $dateLivraison;

        return $this;
    }
}