<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds gatewayReference field to transaction table
 */
final class Version20230714123456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds gatewayReference field to transaction table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction ADD gateway_reference VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction DROP gateway_reference');
    }
} 