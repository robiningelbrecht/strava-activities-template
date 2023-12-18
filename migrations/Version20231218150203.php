<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231218150203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE __temp__KeyValue AS SELECT "key", value FROM KeyValue');
        $this->addSql('DROP TABLE KeyValue');
        $this->addSql('CREATE TABLE KeyValue ("key" VARCHAR(255) NOT NULL, value CLOB NOT NULL, PRIMARY KEY("key"))');
        $this->addSql('INSERT INTO KeyValue ("key", value) SELECT "key", value FROM __temp__KeyValue');
        $this->addSql('DROP TABLE __temp__KeyValue');

        $activityIds = $this->connection->executeQuery('SELECT activityId FROM Activity ORDER BY startDateTime DESC')->fetchFirstColumn();
        $this->addSql(
            'INSERT INTO KeyValue (`key`, `value`) VALUES (:key, :value)',
            [
                'key' => 'imported_activity_streams',
                'value' => implode(',', $activityIds),
            ]
        );
    }

    public function down(Schema $schema): void
    {
    }
}
