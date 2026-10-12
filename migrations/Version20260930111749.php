<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930111749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la relation entre clients et users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE clients
             ADD user_id INT DEFAULT NULL'
        );

        $this->addSql(
            'ALTER TABLE clients
             ADD CONSTRAINT FK_C82E74A76ED395
             FOREIGN KEY (user_id)
             REFERENCES users (id)
             ON DELETE SET NULL'
        );

        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_C82E74A76ED395
             ON clients (user_id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE clients
             DROP FOREIGN KEY FK_C82E74A76ED395'
        );

        $this->addSql(
            'DROP INDEX UNIQ_C82E74A76ED395 ON clients'
        );

        $this->addSql(
            'ALTER TABLE clients
             DROP user_id'
        );
    }
}

