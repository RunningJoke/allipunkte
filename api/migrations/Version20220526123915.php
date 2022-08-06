<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220526123915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE petition (id INT AUTO_INCREMENT NOT NULL, create_user_id INT NOT NULL, create_date DATETIME NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, status VARCHAR(255) DEFAULT \'open\' NOT NULL, due_date DATETIME NOT NULL, open_positions INT DEFAULT 1 NOT NULL, filled_positions INT DEFAULT 0 NOT NULL, INDEX IDX_FF598B0385564492 (create_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE petition ADD CONSTRAINT FK_FF598B0385564492 FOREIGN KEY (create_user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE petition');
    }
}
