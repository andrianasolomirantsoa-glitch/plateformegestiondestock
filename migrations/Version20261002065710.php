<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout de la photo du produit.
 */
final class Version20261002065710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne image à la table produits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produits ADD image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produits DROP image');
    }
}

