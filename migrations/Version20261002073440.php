<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Conversion des tables de commande en InnoDB
 * et ajout des clés étrangères manquantes.
 */
final class Version20261002073440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convertit les tables de commande en InnoDB et ajoute les clés étrangères';
    }

    public function up(Schema $schema): void
    {
        // =========================================================
        // 1. Conversion MyISAM -> InnoDB
        // =========================================================

        $this->addSql(
            'ALTER TABLE commandes ENGINE = InnoDB'
        );

        $this->addSql(
            'ALTER TABLE ligne_commandes ENGINE = InnoDB'
        );

        $this->addSql(
            'ALTER TABLE livraisons ENGINE = InnoDB'
        );

        $this->addSql(
            'ALTER TABLE paiements ENGINE = InnoDB'
        );

        // =========================================================
        // 2. Ajout des clés étrangères
        // =========================================================

        $this->addSql(
            'ALTER TABLE commandes
             ADD CONSTRAINT FK_35D4282CA76ED395
             FOREIGN KEY (user_id)
             REFERENCES users (id)'
        );

        $this->addSql(
            'ALTER TABLE ligne_commandes
             ADD CONSTRAINT FK_FA3127A482EA2E54
             FOREIGN KEY (commande_id)
             REFERENCES commandes (id)
             ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE ligne_commandes
             ADD CONSTRAINT FK_FA3127A4F347EFB
             FOREIGN KEY (produit_id)
             REFERENCES produits (id)'
        );

        $this->addSql(
            'ALTER TABLE livraisons
             ADD CONSTRAINT FK_96A0CE6182EA2E54
             FOREIGN KEY (commande_id)
             REFERENCES commandes (id)
             ON DELETE CASCADE'
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
        // =========================================================
        // 1. Suppression des clés étrangères
        // =========================================================

        $this->addSql(
            'ALTER TABLE paiements
             DROP FOREIGN KEY FK_E1B02E1282EA2E54'
        );

        $this->addSql(
            'ALTER TABLE livraisons
             DROP FOREIGN KEY FK_96A0CE6182EA2E54'
        );

        $this->addSql(
            'ALTER TABLE ligne_commandes
             DROP FOREIGN KEY FK_FA3127A4F347EFB'
        );

        $this->addSql(
            'ALTER TABLE ligne_commandes
             DROP FOREIGN KEY FK_FA3127A482EA2E54'
        );

        $this->addSql(
            'ALTER TABLE commandes
             DROP FOREIGN KEY FK_35D4282CA76ED395'
        );

        // =========================================================
        // 2. Retour vers MyISAM
        // =========================================================

        $this->addSql(
            'ALTER TABLE commandes ENGINE = MyISAM'
        );

        $this->addSql(
            'ALTER TABLE ligne_commandes ENGINE = MyISAM'
        );

        $this->addSql(
            'ALTER TABLE livraisons ENGINE = MyISAM'
        );

        $this->addSql(
            'ALTER TABLE paiements ENGINE = MyISAM'
        );
    }
}

