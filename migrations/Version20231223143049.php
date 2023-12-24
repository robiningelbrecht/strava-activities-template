<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231223143049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE Activity SET activityId = 'activity-' || activityId");
        $this->addSql("UPDATE ActivityStream SET activityId = 'activity-' || activityId");
        $this->addSql("UPDATE SegmentEffort SET activityId = 'activity-' || activityId");
        $this->addSql("UPDATE Challenge SET challengeId = 'challenge-' || challengeId");
        $this->addSql("UPDATE Gear SET gearId = 'gear-' || gearId");
        $this->addSql("UPDATE Activity SET gearId = 'gear-' || gearId WHERE gearId IS NOT NULL");
        $this->addSql("UPDATE Segment SET segmentId = 'segment-' || segmentId");
        $this->addSql("UPDATE SegmentEffort SET segmentId = 'segment-' || segmentId");
        $this->addSql("UPDATE SegmentEffort SET segmentEffortId = 'segmentEffort-' || segmentEffortId");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
