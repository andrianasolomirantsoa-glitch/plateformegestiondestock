<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110075601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables pour la gestion des inventaires par magasin';
    }

    public function up(Schema $schema): void
    {
        // Table principale des inventaires
        $this->addSql('CREATE TABLE inventaires (
            id INT AUTO_INCREMENT NOT NULL,
            date_inventaire DATETIME NOT NULL,
            statut VARCHAR(20) NOT NULL,
            commentaire LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            magasin_id INT NOT NULL,
            utilisateur_id INT DEFAULT NULL,
            INDEX IDX_BB37A45320096AE3 (magasin_id),
            INDEX IDX_BB37A453FB88E14F (utilisateur_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        // Lignes détaillées des inventaires
        $this->addSql('CREATE TABLE lignes_inventaire (
            id INT AUTO_INCREMENT NOT NULL,
            stock_theorique INT NOT NULL,
            quantite_physique INT DEFAULT NULL,
            ecart INT DEFAULT NULL,
            observation LONGTEXT DEFAULT NULL,
            inventaire_id INT NOT NULL,
            produit_id INT NOT NULL,
            INDEX IDX_2E1C86D6CE430A85 (inventaire_id),
            INDEX IDX_2E1C86D6F347EFB (produit_id),
            UNIQUE INDEX uniq_inventaire_produit (inventaire_id, produit_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        // Relations des inventaires
        $this->addSql('ALTER TABLE inventaires
            ADD CONSTRAINT FK_BB37A45320096AE3
            FOREIGN KEY (magasin_id) REFERENCES magasins (id)
            ON DELETE RESTRICT');

        $this->addSql('ALTER TABLE inventaires
            ADD CONSTRAINT FK_BB37A453FB88E14F
            FOREIGN KEY (utilisateur_id) REFERENCES users (id)
            ON DELETE SET NULL');

        // Relations des lignes d'inventaire
        $this->addSql('ALTER TABLE lignes_inventaire
            ADD CONSTRAINT FK_2E1C86D6CE430A85
            FOREIGN KEY (inventaire_id) REFERENCES inventaires (id)
            ON DELETE CASCADE');

        $this->addSql('ALTER TABLE lignes_inventaire
            ADD CONSTRAINT FK_2E1C86D6F347EFB
            FOREIGN KEY (produit_id) REFERENCES produits (id)
            ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // Supprimer d'abord les lignes, puis les inventaires
        $this->addSql('ALTER TABLE lignes_inventaire DROP FOREIGN KEY FK_2E1C86D6CE430A85');
        $this->addSql('ALTER TABLE lignes_inventaire DROP FOREIGN KEY FK_2E1C86D6F347EFB');

        $this->addSql('ALTER TABLE inventaires DROP FOREIGN KEY FK_BB37A45320096AE3');
        $this->addSql('ALTER TABLE inventaires DROP FOREIGN KEY FK_BB37A453FB88E14F');

        $this->addSql('DROP TABLE lignes_inventaire');
        $this->addSql('DROP TABLE inventaires');
    }
}
