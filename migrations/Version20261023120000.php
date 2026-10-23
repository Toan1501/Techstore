<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add coupon usage limit (max_uses) and usage counter (usage_count).
 */
final class Version20261023120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add coupon.max_uses and coupon.usage_count';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coupon ADD max_uses INT DEFAULT NULL, ADD usage_count INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coupon DROP max_uses, DROP usage_count');
    }
}
