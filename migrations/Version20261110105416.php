<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261110105416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inventaires ADD CONSTRAINT FK_BB37A45320096AE3 FOREIGN KEY (magasin_id) REFERENCES magasins (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE inventaires ADD CONSTRAINT FK_BB37A453FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lignes_inventaire ADD CONSTRAINT FK_2E1C86D6CE430A85 FOREIGN KEY (inventaire_id) REFERENCES inventaires (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lignes_inventaire ADD CONSTRAINT FK_2E1C86D6F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE users ADD photo_profil VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inventaires DROP FOREIGN KEY FK_BB37A45320096AE3');
        $this->addSql('ALTER TABLE inventaires DROP FOREIGN KEY FK_BB37A453FB88E14F');
        $this->addSql('ALTER TABLE lignes_inventaire DROP FOREIGN KEY FK_2E1C86D6CE430A85');
        $this->addSql('ALTER TABLE lignes_inventaire DROP FOREIGN KEY FK_2E1C86D6F347EFB');
        $this->addSql('ALTER TABLE users DROP photo_profil');
    }
}
