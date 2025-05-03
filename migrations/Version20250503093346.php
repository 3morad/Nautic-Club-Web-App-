<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503093346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE categories
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE feedback
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE activities
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_feedback
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE equipment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE locations
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE events
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tickets
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE password_reset_tokens
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE rentals
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX username ON users
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX email ON users
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE username username VARCHAR(255) NOT NULL, CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE password password VARCHAR(255) NOT NULL, CHANGE user_type user_type VARCHAR(50) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, image_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE feedback (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, event_id INT NOT NULL, comment TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, rating INT DEFAULT NULL, INDEX user_id (user_id), INDEX event_id (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE activities (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, difficulty_level VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, image_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, category_id INT DEFAULT NULL, INDEX category_id (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_feedback (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, message TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE equipment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, availability TINYINT(1) DEFAULT 1, price NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE locations (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE events (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, price NUMERIC(10, 2) NOT NULL, time DATETIME NOT NULL, location_id INT DEFAULT NULL, INDEX location_id (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tickets (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, user_id INT NOT NULL, issue_date DATE DEFAULT 'curdate()', INDEX event_id (event_id), INDEX user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE password_reset_tokens (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE rentals (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, equipment_id INT NOT NULL, status VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT 'rented' COLLATE `utf8mb4_0900_ai_ci`, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, INDEX user_id (user_id), INDEX equipment_id (equipment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE username username VARCHAR(50) DEFAULT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL, CHANGE user_type user_type VARCHAR(20) DEFAULT 'user', CHANGE first_name first_name VARCHAR(50) DEFAULT NULL, CHANGE last_name last_name VARCHAR(50) DEFAULT NULL, CHANGE email email VARCHAR(100) DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT 'active'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX username ON users (username)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX email ON users (email)
        SQL);
    }
}
