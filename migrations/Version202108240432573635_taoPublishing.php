<?php

declare(strict_types=1);

namespace oat\taoPublishing\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\tao\model\search\SearchProxy;
use oat\taoPublishing\model\PlatformService;

final class Version202108240432573635_taoPublishing extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Register Remote Environment class URI to use generic search in ' . SearchProxy::class;
    }

    public function up(Schema $schema): void
    {
        $searchProxy = $this->getProxy();
        $searchProxy->extendGenerisSearchWhiteList([
            PlatformService::CLASS_URI
        ]);
        $this->getServiceManager()->register(SearchProxy::SERVICE_ID, $searchProxy);
    }

    public function down(Schema $schema): void
    {
        $searchProxy = $this->getProxy();
        $searchProxy->removeFromGenerisSearchWhiteList([
            PlatformService::CLASS_URI
        ]);
        $this->getServiceManager()->register(SearchProxy::SERVICE_ID, $searchProxy);
    }

    private function getProxy(): SearchProxy
    {
        return $this->getServiceManager()->get(SearchProxy::SERVICE_ID);
    }
}
