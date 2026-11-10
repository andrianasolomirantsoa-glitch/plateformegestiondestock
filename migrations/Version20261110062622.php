<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110062622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Associer les anciennes sorties au magasin principal ID 5.';
    }

    public function up(Schema $schema): void
    {
        // 1. Ajouter temporairement la colonne nullable.
        $this->addSql(
            'ALTER TABLE sorties_stock ADD magasin_id INT DEFAULT NULL'
        );

        // 2. Affecter le magasin principal aux anciennes sorties.
        $this->addSql(
            'UPDATE sorties_stock SET magasin_id = 5 WHERE magasin_id IS NULL'
        );

        // 3. Rendre la relation obligatoire.
        $this->addSql(
            'ALTER TABLE sorties_stock MODIFY magasin_id INT NOT NULL'
        );

        // 4. Ajouter la clé étrangère.
        $this->addSql(
            'ALTER TABLE sorties_stock
             ADD CONSTRAINT FK_CEE723A820096AE3
             FOREIGN KEY (magasin_id)
             REFERENCES magasins (id)
             ON DELETE RESTRICT'
        );

        // 5. Ajouter un index.
        $this->addSql(
            'CREATE INDEX IDX_CEE723A820096AE3
             ON sorties_stock (magasin_id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE sorties_stock
             DROP FOREIGN KEY FK_CEE723A820096AE3'
        );

        $this->addSql(
            'DROP INDEX IDX_CEE723A820096AE3 ON sorties_stock'
        );

        $this->addSql(
            'ALTER TABLE sorties_stock DROP magasin_id'
        );
    }
}
