<?php
/**
 * Default config header created during install
 */

return new oat\taoPublishing\model\publishing\PublishingService([
    \oat\taoPublishing\model\publishing\PublishingService::OPTIONS_ACTIONS => [
         \oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent::class,
         \oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent::class
    ]
]);
