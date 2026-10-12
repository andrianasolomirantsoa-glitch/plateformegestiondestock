<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930074013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table livraisons';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE livraisons (
                id INT AUTO_INCREMENT NOT NULL,
                mode_livraison VARCHAR(30) NOT NULL,
                statut VARCHAR(30) NOT NULL,
                adresse_livraison VARCHAR(255) NOT NULL,
                ville VARCHAR(100) NOT NULL,
                pays VARCHAR(100) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                date_livraison DATETIME DEFAULT NULL,
                commande_id INT NOT NULL,
                UNIQUE INDEX UNIQ_96A0CE6182EA2E54 (commande_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4'
        );

        $this->addSql(
            'ALTER TABLE livraisons
             ADD CONSTRAINT FK_96A0CE6182EA2E54
             FOREIGN KEY (commande_id)
             REFERENCES commandes (id)
             ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE livraisons
             DROP FOREIGN KEY FK_96A0CE6182EA2E54'
        );

        $this->addSql('DROP TABLE livraisons');
    }
}

