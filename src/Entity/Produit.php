<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produits')]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 100, unique: true)]
    private ?string $reference = null;


    #[ORM\Column(length: 150)]
    private ?string $nom = null;


    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;


    #[ORM\Column(name: 'prix_achat', type: 'decimal', precision: 12, scale: 2)]
    private ?string $prixAchat = null;


    #[ORM\Column(name: 'prix_vente', type: 'decimal', precision: 12, scale: 2)]
    private ?string $prixVente = null;


    /**
     * Photo principale du produit.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;


    #[ORM\Column(name: 'quantite_stock')]
    private int $quantiteStock = 0;


    #[ORM\Column(name: 'seuil_alerte')]
    private int $seuilAlerte = 0;


    #[ORM\Column(length: 30)]
    private string $unite = 'piece';


    #[ORM\Column]
    private bool $actif = true;


    #[ORM\ManyToOne(inversedBy: 'produits')]
    #[ORM\JoinColumn(name: 'categorie_id', nullable: false)]
    private ?Categorie $categorie = null;


    #[ORM\Column(name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;


    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;


    /**
     * Photos secondaires du produit.
     */
    #[ORM\OneToMany(
        mappedBy: 'produit',
        targetEntity: ProduitImage::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $images;


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();

        $this->images = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }


    public function getReference(): ?string
    {
        return $this->reference;
    }


    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }


    public function getNom(): ?string
    {
        return $this->nom;
    }


    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }


    public function getDescription(): ?string
    {
        return $this->description;
    }


    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }


    public function getPrixAchat(): ?string
    {
        return $this->prixAchat;
    }


    public function setPrixAchat(string $prixAchat): static
    {
        $this->prixAchat = $prixAchat;

        return $this;
    }


    public function getPrixVente(): ?string
    {
        return $this->prixVente;
    }


    public function setPrixVente(string $prixVente): static
    {
        $this->prixVente = $prixVente;

        return $this;
    }


    /**
     * Photo principale.
     */
    public function getImage(): ?string
    {
        return $this->image;
    }


    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }


    public function getQuantiteStock(): int
    {
        return $this->quantiteStock;
    }


    public function setQuantiteStock(int $quantiteStock): static
    {
        $this->quantiteStock = $quantiteStock;

        return $this;
    }


    public function getSeuilAlerte(): int
    {
        return $this->seuilAlerte;
    }


    public function setSeuilAlerte(int $seuilAlerte): static
    {
        $this->seuilAlerte = $seuilAlerte;

        return $this;
    }


    public function getUnite(): string
    {
        return $this->unite;
    }


    public function setUnite(string $unite): static
    {
        $this->unite = $unite;

        return $this;
    }


    public function isActif(): bool
    {
        return $this->actif;
    }


    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }


    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }


    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

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


    /**
     * @return Collection<int, ProduitImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }


    public function addImage(ProduitImage $image): static
    {
        if (!$this->images->contains($image)) {

            $this->images->add($image);

            $image->setProduit($this);
        }

        return $this;
    }


    public function removeImage(ProduitImage $image): static
    {
        if ($this->images->removeElement($image)) {

            if ($image->getProduit() === $this) {

                $image->setProduit(null);
            }
        }

        return $this;
    }
}

