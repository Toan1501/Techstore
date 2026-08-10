<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260810092546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE store_settings (id INT AUTO_INCREMENT NOT NULL, store_name VARCHAR(120) NOT NULL, hotline VARCHAR(40) DEFAULT NULL, email VARCHAR(120) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, bank_name VARCHAR(80) DEFAULT NULL, bank_account VARCHAR(40) DEFAULT NULL, bank_owner VARCHAR(120) DEFAULT NULL, free_ship_threshold INT DEFAULT 5000000 NOT NULL, ship_fee_inner INT DEFAULT 25000 NOT NULL, ship_fee_outer INT DEFAULT 40000 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE store_settings');
    }
}
