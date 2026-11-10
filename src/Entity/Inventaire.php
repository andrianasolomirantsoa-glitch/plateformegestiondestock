<?php

namespace App\Entity;

use App\Repository\InventaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventaireRepository::class)]
#[ORM\Table(name: 'inventaires')]
class Inventaire
{
    public const STATUT_EN_COURS = 'EN_COURS';
    public const STATUT_VALIDE = 'VALIDE';
    public const STATUT_ANNULE = 'ANNULE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Magasin::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Magasin $magasin = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $utilisateur = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateInventaire;

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUT_EN_COURS;

    #[ORM\Column(type: 'text', nullable: true)]
private ?string $commentaire = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, LigneInventaire>
     */
    #[ORM\OneToMany(
        mappedBy: 'inventaire',
        targetEntity: LigneInventaire::class,
        cascade: ['persist']
    )]
    private Collection $lignes;

    public function __construct()
    {
        $this->dateInventaire = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMagasin(): ?Magasin
    {
        return $this->magasin;
    }

    public function setMagasin(Magasin $magasin): static
    {
        $this->magasin = $magasin;
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

    public function getDateInventaire(): \DateTimeImmutable
    {
        return $this->dateInventaire;
    }

    public function setDateInventaire(\DateTimeImmutable $dateInventaire): static
    {
        $this->dateInventaire = $dateInventaire;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        if (!in_array($statut, [
            self::STATUT_EN_COURS,
            self::STATUT_VALIDE,
            self::STATUT_ANNULE,
        ], true)) {
            throw new \InvalidArgumentException('Statut d’inventaire invalide.');
        }

        $this->statut = $statut;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, LigneInventaire>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneInventaire $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setInventaire($this);
        }

        return $this;
    }

    public function removeLigne(LigneInventaire $ligne): static
    {
        $this->lignes->removeElement($ligne);
        return $this;
    }
}
