<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Reviews without an account (author_name) + demo flag so sample reviews can be purged. */
final class Version20261024150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'review: nullable user (SET NULL), author_name, demo flag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6A76ED395');
        $this->addSql('ALTER TABLE review ADD author_name VARCHAR(80) DEFAULT NULL, ADD demo TINYINT(1) DEFAULT 0 NOT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM review WHERE user_id IS NULL');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6A76ED395');
        $this->addSql('ALTER TABLE review DROP author_name, DROP demo, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
