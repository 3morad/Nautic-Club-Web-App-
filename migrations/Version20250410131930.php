<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410131930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ticket ADD created_by_id INT DEFAULT NULL, ADD title VARCHAR(255) NOT NULL, ADD description VARCHAR(255) DEFAULT NULL, ADD category VARCHAR(50) NOT NULL, ADD status VARCHAR(50) NOT NULL, ADD priority VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_97A0ADA3B03A8386 ON ticket (created_by_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3B03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_97A0ADA3B03A8386 ON ticket
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ticket DROP created_by_id, DROP title, DROP description, DROP category, DROP status, DROP priority
        SQL);
    }
}
