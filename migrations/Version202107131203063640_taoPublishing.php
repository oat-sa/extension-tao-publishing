<?php

declare(strict_types=1);

namespace oat\taoPublishing\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\model\action\ActionBlackList;
use oat\tao\scripts\tools\migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202107131203063640_taoPublishing extends AbstractMigration
{

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $actionBlacklistService = $this->getServiceManager()->get(ActionBlackList::SERVICE_ID);
        $existingActions = $actionBlacklistService->getOption(ActionBlackList::OPTION_DISABLED_ACTIONS);
        $existingActions[] = 'class-remote-publish';
        $existingActions = array_unique($existingActions);
        $actionBlacklistService->setOption(ActionBlackList::OPTION_DISABLED_ACTIONS, $existingActions);
        $this->getServiceManager()->register(ActionBlackList::SERVICE_ID, $actionBlacklistService);
    }

    public function down(Schema $schema): void
    {
    }
}
