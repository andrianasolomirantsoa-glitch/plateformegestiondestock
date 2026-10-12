<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930070153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table paiements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE paiements (
                id INT AUTO_INCREMENT NOT NULL,
                mode VARCHAR(30) NOT NULL,
                statut VARCHAR(30) NOT NULL,
                montant NUMERIC(12, 2) NOT NULL,
                reference VARCHAR(100) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                commande_id INT NOT NULL,
                UNIQUE INDEX UNIQ_E1B02E1282EA2E54 (commande_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4'
        );

        $this->addSql(
            'ALTER TABLE paiements
             ADD CONSTRAINT FK_E1B02E1282EA2E54
             FOREIGN KEY (commande_id)
             REFERENCES commandes (id)
             ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE paiements
             DROP FOREIGN KEY FK_E1B02E1282EA2E54'
        );

        $this->addSql('DROP TABLE paiements');
    }
}