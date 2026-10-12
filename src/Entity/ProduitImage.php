<?php

namespace App\Entity;

use App\Repository\ProduitImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitImageRepository::class)]
#[ORM\Table(
    name: 'produit_images',
    options: ['engine' => 'InnoDB']
)]
class ProduitImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 255)]
    private ?string $filename = null;


    #[ORM\Column]
    private int $ordre = 0;


    #[ORM\ManyToOne(
        inversedBy: 'images'
    )]
    #[ORM\JoinColumn(
        name: 'produit_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ?Produit $produit = null;


    public function getId(): ?int
    {
        return $this->id;
    }


    public function getFilename(): ?string
    {
        return $this->filename;
    }


    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }


    public function getOrdre(): int
    {
        return $this->ordre;
    }


    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

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
}

