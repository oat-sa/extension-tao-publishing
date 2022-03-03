<?php

declare(strict_types=1);

namespace oat\taoPublishing\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\tao\scripts\update\OntologyUpdater;

final class Version202203030836323635_taoPublishing extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Add property AssessmentProjectId on test model';
    }

    public function up(Schema $schema): void
    {
        OntologyUpdater::syncModels();
    }

    public function down(Schema $schema): void
    {
        OntologyUpdater::syncModels();
    }
}
