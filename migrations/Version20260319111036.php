<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260319111036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE apartment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, is_available BOOLEAN NOT NULL, price INTEGER NOT NULL, description CLOB DEFAULT NULL, vapi_synced_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE TABLE apartment_apartment_group (apartment_id INTEGER NOT NULL, apartment_group_id INTEGER NOT NULL, PRIMARY KEY (apartment_id, apartment_group_id), CONSTRAINT FK_1D56EA73176DFE85 FOREIGN KEY (apartment_id) REFERENCES apartment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1D56EA73F3B58597 FOREIGN KEY (apartment_group_id) REFERENCES apartment_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1D56EA73176DFE85 ON apartment_apartment_group (apartment_id)');
        $this->addSql('CREATE INDEX IDX_1D56EA73F3B58597 ON apartment_apartment_group (apartment_group_id)');
        $this->addSql('CREATE TABLE apartment_group (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, parent_id INTEGER DEFAULT NULL, CONSTRAINT FK_2A071708727ACA70 FOREIGN KEY (parent_id) REFERENCES apartment_group (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2A071708727ACA70 ON apartment_group (parent_id)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)');
        $this->addSql('CREATE TABLE user_apartment_group (user_id INTEGER NOT NULL, apartment_group_id INTEGER NOT NULL, PRIMARY KEY (user_id, apartment_group_id), CONSTRAINT FK_38DB7B87A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_38DB7B87F3B58597 FOREIGN KEY (apartment_group_id) REFERENCES apartment_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_38DB7B87A76ED395 ON user_apartment_group (user_id)');
        $this->addSql('CREATE INDEX IDX_38DB7B87F3B58597 ON user_apartment_group (apartment_group_id)');
        $this->addSql('CREATE TABLE vapi_assistant_config (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, prompt CLOB NOT NULL, first_message CLOB NOT NULL, time_limit INTEGER NOT NULL, updated_at DATETIME NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE apartment');
        $this->addSql('DROP TABLE apartment_apartment_group');
        $this->addSql('DROP TABLE apartment_group');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_apartment_group');
        $this->addSql('DROP TABLE vapi_assistant_config');
    }
}
