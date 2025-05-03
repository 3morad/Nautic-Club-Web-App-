<?php

namespace App\Entity;

class CreateMessengerMessagesTable
{
    public static function getCreateTableSQL(): string
    {
        return "
        CREATE TABLE messenger_messages (
            id BIGINT AUTO_INCREMENT NOT NULL, 
            body LONGTEXT NOT NULL, 
            headers LONGTEXT NOT NULL, 
            queue_name VARCHAR(190) NOT NULL, 
            created_at DATETIME NOT NULL, 
            available_at DATETIME NOT NULL, 
            delivered_at DATETIME DEFAULT NULL, 
            INDEX IDX_75EA56E0FB7336F0 (queue_name), 
            INDEX IDX_75EA56E0E3BD61CE (available_at), 
            INDEX IDX_75EA56E016BA31DB (delivered_at), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
        ";
    }
} 