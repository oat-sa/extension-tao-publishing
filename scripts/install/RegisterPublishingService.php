<?php

namespace oat\taoPublishing\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoPublishing\model\publishing\PublishingService;

class RegisterPublishingService extends InstallAction
{
    public function __invoke($params)
    {
        $service = new PublishingService();
        $this->registerService(PublishingService::SERVICE_ID, $service);
    }
}