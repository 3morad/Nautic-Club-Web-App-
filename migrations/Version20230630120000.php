<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230630120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Event and LocationWeather tables';
    }

    public function up(Schema $schema): void
    {
        // Create LocationWeather table
        $this->addSql('CREATE TABLE location_weather (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            latitude DOUBLE PRECISION NOT NULL,
            longitude DOUBLE PRECISION NOT NULL,
            region VARCHAR(255) NOT NULL,
            temperature DOUBLE PRECISION NOT NULL,
            weather_condition VARCHAR(255) NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create Event table with relation to LocationWeather
        $this->addSql('CREATE TABLE event (
            id INT AUTO_INCREMENT NOT NULL,
            location_weather_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            event_date DATETIME NOT NULL,
            INDEX IDX_3BAE0AA7F9C0694D (location_weather_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7F9C0694D FOREIGN KEY (location_weather_id) REFERENCES location_weather (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop tables in correct order (to avoid foreign key constraint issues)
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7F9C0694D');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE location_weather');
    }
} 