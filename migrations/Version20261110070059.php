<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110070059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du magasin associé aux entrées de stock';
    }

    public function up(Schema $schema): void
    {
        // Ajouter temporairement la colonne en nullable.
        $this->addSql('ALTER TABLE entrees_stock ADD magasin_id INT DEFAULT NULL');

        // Aucune ancienne entrée n'existe actuellement.
        // Le magasin 5 est utilisé comme valeur de sécurité.
        $this->addSql('UPDATE entrees_stock SET magasin_id = 5 WHERE magasin_id IS NULL');

        // Rendre la colonne obligatoire.
        $this->addSql('ALTER TABLE entrees_stock MODIFY magasin_id INT NOT NULL');

        // Ajouter la clé étrangère et l'index.
        $this->addSql('ALTER TABLE entrees_stock ADD CONSTRAINT FK_423F5FD020096AE3 FOREIGN KEY (magasin_id) REFERENCES magasins (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_423F5FD020096AE3 ON entrees_stock (magasin_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entrees_stock DROP FOREIGN KEY FK_423F5FD020096AE3');
        $this->addSql('DROP INDEX IDX_423F5FD020096AE3 ON entrees_stock');
        $this->addSql('ALTER TABLE entrees_stock DROP magasin_id');
    }
}
