<?php

namespace App\Entity;

use App\Repository\StockMagasinRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockMagasinRepository::class)]
#[ORM\Table(name: 'stocks_magasins')]
#[ORM\UniqueConstraint(
    name: 'uniq_stock_produit_magasin',
    columns: ['produit_id', 'magasin_id']
)]
class StockMagasin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Produit $produit = null;

    #[ORM\ManyToOne(targetEntity: Magasin::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Magasin $magasin = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $quantite = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $seuilAlerte = 0;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMagasin(): ?Magasin
    {
        return $this->magasin;
    }

    public function setMagasin(Magasin $magasin): static
    {
        $this->magasin = $magasin;

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

    public function getSeuilAlerte(): int
    {
        return $this->seuilAlerte;
    }

    public function setSeuilAlerte(int $seuilAlerte): static
    {
        $this->seuilAlerte = $seuilAlerte;

        return $this;
    }
}
