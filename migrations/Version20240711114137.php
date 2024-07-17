<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240711114137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE authz_member ADD institution_service_id INT NOT NULL');
        $this->addSql('ALTER TABLE authz_member ADD CONSTRAINT FK_7665513AB285C3C FOREIGN KEY (institution_service_id) REFERENCES institution_service (id)');
        $this->addSql('CREATE INDEX IDX_7665513AB285C3C ON authz_member (institution_service_id)');
        $this->addSql('ALTER TABLE institution_service DROP authz_members');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE institution_service ADD authz_members JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE authz_member DROP FOREIGN KEY FK_7665513AB285C3C');
        $this->addSql('DROP INDEX IDX_7665513AB285C3C ON authz_member');
        $this->addSql('ALTER TABLE authz_member DROP institution_service_id');
    }
}
