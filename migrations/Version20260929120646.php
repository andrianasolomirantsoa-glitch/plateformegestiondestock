<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929120646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commandes (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(30) NOT NULL, statut VARCHAR(30) NOT NULL, total NUMERIC(12, 2) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_35D4282CF55AE19E (numero), INDEX IDX_35D4282CA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ligne_commandes (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(12, 2) NOT NULL, sous_total NUMERIC(12, 2) NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_FA3127A482EA2E54 (commande_id), INDEX IDX_FA3127A4F347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT FK_35D4282CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ligne_commandes ADD CONSTRAINT FK_FA3127A482EA2E54 FOREIGN KEY (commande_id) REFERENCES commandes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ligne_commandes ADD CONSTRAINT FK_FA3127A4F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id)');
        $this->addSql('ALTER TABLE categories CHANGE description description LONGTEXT DEFAULT NULL, CHANGE actif actif TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE categories RENAME INDEX nom TO UNIQ_3AF346686C6E55B5');
        $this->addSql('ALTER TABLE clients CHANGE actif actif TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE entrees_stock DROP FOREIGN KEY `fk_entree_fournisseur`');
        $this->addSql('ALTER TABLE entrees_stock DROP FOREIGN KEY `fk_entree_produit`');
        $this->addSql('ALTER TABLE entrees_stock DROP FOREIGN KEY `fk_entree_utilisateur`');
        $this->addSql('DROP INDEX idx_entree_date ON entrees_stock');
        $this->addSql('ALTER TABLE entrees_stock CHANGE prix_unitaire prix_unitaire NUMERIC(12, 2) NOT NULL, CHANGE date_entree date_entree DATETIME NOT NULL, CHANGE commentaire commentaire LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE entrees_stock ADD CONSTRAINT FK_423F5FD0F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id)');
        $this->addSql('ALTER TABLE entrees_stock ADD CONSTRAINT FK_423F5FD0670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs (id)');
        $this->addSql('ALTER TABLE entrees_stock ADD CONSTRAINT FK_423F5FD0FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE entrees_stock RENAME INDEX idx_entree_produit TO IDX_423F5FD0F347EFB');
        $this->addSql('ALTER TABLE entrees_stock RENAME INDEX idx_entree_fournisseur TO IDX_423F5FD0670C757F');
        $this->addSql('ALTER TABLE entrees_stock RENAME INDEX fk_entree_utilisateur TO IDX_423F5FD0FB88E14F');
        $this->addSql('ALTER TABLE fournisseurs CHANGE actif actif TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE produits DROP FOREIGN KEY `fk_produit_categorie`');
        $this->addSql('DROP INDEX idx_produit_nom ON produits');
        $this->addSql('DROP INDEX idx_produit_reference ON produits');
        $this->addSql('ALTER TABLE produits CHANGE description description LONGTEXT DEFAULT NULL, CHANGE prix_achat prix_achat NUMERIC(12, 2) NOT NULL, CHANGE prix_vente prix_vente NUMERIC(12, 2) NOT NULL, CHANGE quantite_stock quantite_stock INT NOT NULL, CHANGE seuil_alerte seuil_alerte INT NOT NULL, CHANGE unite unite VARCHAR(30) NOT NULL, CHANGE actif actif TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE produits ADD CONSTRAINT FK_BE2DDF8CBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE produits RENAME INDEX reference TO UNIQ_BE2DDF8CAEA34913');
        $this->addSql('ALTER TABLE produits RENAME INDEX idx_produit_categorie TO IDX_BE2DDF8CBCF5E72D');
        $this->addSql('ALTER TABLE sorties_stock DROP FOREIGN KEY `fk_sortie_client`');
        $this->addSql('ALTER TABLE sorties_stock DROP FOREIGN KEY `fk_sortie_produit`');
        $this->addSql('ALTER TABLE sorties_stock DROP FOREIGN KEY `fk_sortie_utilisateur`');
        $this->addSql('DROP INDEX idx_sortie_date ON sorties_stock');
        $this->addSql('ALTER TABLE sorties_stock CHANGE prix_unitaire prix_unitaire NUMERIC(12, 2) NOT NULL, CHANGE date_sortie date_sortie DATETIME NOT NULL, CHANGE commentaire commentaire LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE sorties_stock ADD CONSTRAINT FK_CEE723A8F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id)');
        $this->addSql('ALTER TABLE sorties_stock ADD CONSTRAINT FK_CEE723A819EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE sorties_stock ADD CONSTRAINT FK_CEE723A8FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE sorties_stock RENAME INDEX idx_sortie_produit TO IDX_CEE723A8F347EFB');
        $this->addSql('ALTER TABLE sorties_stock RENAME INDEX idx_sortie_client TO IDX_CEE723A819EB6921');
        $this->addSql('ALTER TABLE sorties_stock RENAME INDEX fk_sortie_utilisateur TO IDX_CEE723A8FB88E14F');
        $this->addSql('ALTER TABLE users CHANGE actif actif TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE users RENAME INDEX email TO UNIQ_1483A5E9E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY FK_35D4282CA76ED395');
        $this->addSql('ALTER TABLE ligne_commandes DROP FOREIGN KEY FK_FA3127A482EA2E54');
        $this->addSql('ALTER TABLE ligne_commandes DROP FOREIGN KEY FK_FA3127A4F347EFB');
        $this->addSql('DROP TABLE commandes');
        $this->addSql('DROP TABLE ligne_commandes');
        $this->addSql('ALTER TABLE categories CHANGE description description TEXT DEFAULT NULL, CHANGE actif actif TINYINT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE categories RENAME INDEX uniq_3af346686c6e55b5 TO nom');
        $this->addSql('ALTER TABLE clients CHANGE actif actif TINYINT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE entrees_stock DROP FOREIGN KEY FK_423F5FD0F347EFB');
        $this->addSql('ALTER TABLE entrees_stock DROP FOREIGN KEY FK_423F5FD0670C757F');
        $this->addSql('ALTER TABLE entrees_stock DROP FOREIGN KEY FK_423F5FD0FB88E14F');
        $this->addSql('ALTER TABLE entrees_stock CHANGE prix_unitaire prix_unitaire NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE date_entree date_entree DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE commentaire commentaire TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE entrees_stock ADD CONSTRAINT `fk_entree_fournisseur` FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs (id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('ALTER TABLE entrees_stock ADD CONSTRAINT `fk_entree_produit` FOREIGN KEY (produit_id) REFERENCES produits (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE entrees_stock ADD CONSTRAINT `fk_entree_utilisateur` FOREIGN KEY (utilisateur_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_entree_date ON entrees_stock (date_entree)');
        $this->addSql('ALTER TABLE entrees_stock RENAME INDEX idx_423f5fd0fb88e14f TO fk_entree_utilisateur');
        $this->addSql('ALTER TABLE entrees_stock RENAME INDEX idx_423f5fd0670c757f TO idx_entree_fournisseur');
        $this->addSql('ALTER TABLE entrees_stock RENAME INDEX idx_423f5fd0f347efb TO idx_entree_produit');
        $this->addSql('ALTER TABLE fournisseurs CHANGE actif actif TINYINT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE produits DROP FOREIGN KEY FK_BE2DDF8CBCF5E72D');
        $this->addSql('ALTER TABLE produits CHANGE description description TEXT DEFAULT NULL, CHANGE prix_achat prix_achat NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE prix_vente prix_vente NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE quantite_stock quantite_stock INT DEFAULT 0 NOT NULL, CHANGE seuil_alerte seuil_alerte INT DEFAULT 0 NOT NULL, CHANGE unite unite VARCHAR(30) DEFAULT \'piece\' NOT NULL, CHANGE actif actif TINYINT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE produits ADD CONSTRAINT `fk_produit_categorie` FOREIGN KEY (categorie_id) REFERENCES categories (id) ON UPDATE CASCADE');
        $this->addSql('CREATE INDEX idx_produit_nom ON produits (nom)');
        $this->addSql('CREATE INDEX idx_produit_reference ON produits (reference)');
        $this->addSql('ALTER TABLE produits RENAME INDEX idx_be2ddf8cbcf5e72d TO idx_produit_categorie');
        $this->addSql('ALTER TABLE produits RENAME INDEX uniq_be2ddf8caea34913 TO reference');
        $this->addSql('ALTER TABLE sorties_stock DROP FOREIGN KEY FK_CEE723A8F347EFB');
        $this->addSql('ALTER TABLE sorties_stock DROP FOREIGN KEY FK_CEE723A819EB6921');
        $this->addSql('ALTER TABLE sorties_stock DROP FOREIGN KEY FK_CEE723A8FB88E14F');
        $this->addSql('ALTER TABLE sorties_stock CHANGE prix_unitaire prix_unitaire NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE date_sortie date_sortie DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE commentaire commentaire TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE sorties_stock ADD CONSTRAINT `fk_sortie_client` FOREIGN KEY (client_id) REFERENCES clients (id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sorties_stock ADD CONSTRAINT `fk_sortie_produit` FOREIGN KEY (produit_id) REFERENCES produits (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE sorties_stock ADD CONSTRAINT `fk_sortie_utilisateur` FOREIGN KEY (utilisateur_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_sortie_date ON sorties_stock (date_sortie)');
        $this->addSql('ALTER TABLE sorties_stock RENAME INDEX idx_cee723a8fb88e14f TO fk_sortie_utilisateur');
        $this->addSql('ALTER TABLE sorties_stock RENAME INDEX idx_cee723a819eb6921 TO idx_sortie_client');
        $this->addSql('ALTER TABLE sorties_stock RENAME INDEX idx_cee723a8f347efb TO idx_sortie_produit');
        $this->addSql('ALTER TABLE users CHANGE actif actif TINYINT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE users RENAME INDEX uniq_1483a5e9e7927c74 TO email');
    }
}
