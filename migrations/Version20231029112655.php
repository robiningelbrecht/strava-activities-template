<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231029112655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Ftp (setOn DATE NOT NULL --(DC2Type:date_immutable)
        , ftp INTEGER NOT NULL, PRIMARY KEY(setOn))');
        $this->addSql('CREATE TABLE KeyValue ("key" VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY("key"))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE Ftp');
        $this->addSql('DROP TABLE KeyValue');
    }
}
