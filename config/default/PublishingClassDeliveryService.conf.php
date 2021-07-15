<?php
/**
 * Default config header created during install
 */

use oat\taoPublishing\model\publishing\delivery\PublishingClassDeliveryService;

return new PublishingClassDeliveryService(
    [
        PublishingClassDeliveryService::OPTION_MAX_RESOURCE => 50
    ]
);
