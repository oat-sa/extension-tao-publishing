<?php
/**
 * Default config header created during install
 */

return new oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService([
    \oat\taoPublishing\model\publishing\PublishingService::OPTIONS_FIELDS => [],
    \oat\taoPublishing\model\publishing\PublishingService::OPTIONS_EXCLUDED_FIELDS => [
        \oat\taoDeliveryRdf\model\DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY,
        \oat\taoDeliveryRdf\model\ContainerRuntime::PROPERTY_CONTAINER,
        \oat\taoDeliveryRdf\model\DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME,
        \oat\taoDeliveryRdf\model\DeliveryAssemblyService::PROPERTY_DELIVERY_TIME,
        \oat\taoDeliveryRdf\model\DeliveryAssemblyService::PROPERTY_ORIGIN,
        \oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService::ORIGIN_DELIVERY_ID_FIELD,
        \oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService::DELIVERY_REMOTE_SYNC_FIELD,

        // Using strings for ignoring taoClientRestricted in require
        'http://www.tao.lu/Ontologies/TAODelivery.rdf#RestrictBrowserUsage',
        'http://www.tao.lu/Ontologies/TAODelivery.rdf#RestrictOSUsage'
    ],
]);
