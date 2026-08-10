<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260810120745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_order_status ON `order` (status)');
        $this->addSql('CREATE INDEX idx_order_created_at ON `order` (created_at)');
        $this->addSql('ALTER TABLE `order` RENAME INDEX idx_f5299398a76ed395 TO idx_order_user');
        $this->addSql('CREATE INDEX idx_sanpham_slug ON san_pham (slug)');
        $this->addSql('CREATE INDEX idx_sanpham_brand ON san_pham (brand)');
        $this->addSql('CREATE INDEX idx_sanpham_sku ON san_pham (sku)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_order_status ON `order`');
        $this->addSql('DROP INDEX idx_order_created_at ON `order`');
        $this->addSql('ALTER TABLE `order` RENAME INDEX idx_order_user TO IDX_F5299398A76ED395');
        $this->addSql('DROP INDEX idx_sanpham_slug ON san_pham');
        $this->addSql('DROP INDEX idx_sanpham_brand ON san_pham');
        $this->addSql('DROP INDEX idx_sanpham_sku ON san_pham');
    }
}
