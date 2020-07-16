<?php
/**
 * This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2017 (original work) Open Assessment Technologies SA
 */

namespace oat\taoPublishing\model\publishing\delivery;

use core_kernel_classes_Class;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoPublishing\model\publishing\PublishingService;
use tao_helpers_form_GenerisFormFactory;

/**
 * Class PublishingDeliveryService
 * @package oat\taoPublishing\model\publishing
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 */
class PublishingDeliveryService extends ConfigurableService
{
    use OntologyAwareTrait;

    public const SERVICE_ID = 'taoPublishing/PublishingDeliveryService';
    public const ORIGIN_DELIVERY_ID_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#OriginDeliveryID';
    public const ORIGIN_TEST_ID_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#OriginTestID';
    public const DELIVERY_REMOTE_SYNC_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#RemoteSync';
    public const DELIVERY_REMOTE_SYNC_COMPILE_ENABLED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled';

    public const DELIVERY_REMOTE_SYNC_REST_OPTION = 'remote-publish';

    public function getSynchronizedDeliveryProperties(core_kernel_classes_Resource $delivery): array
    {
        $propertyList = [];
        foreach ($this->collectSynchronizedDeliveryFields() as $deliveryProperty) {
            $value = $delivery->getOnePropertyValue($this->getProperty($deliveryProperty));
            if ($value instanceof core_kernel_classes_Resource) {
                $value = $value->getUri();
            }
            $propertyList[$deliveryProperty] = (string) $value;
        }
        $propertyList[self::ORIGIN_DELIVERY_ID_FIELD] = $delivery->getUri();

        return $propertyList;
    }

    private function collectSynchronizedDeliveryFields(): array
    {
        $deliveryFieldsOptions = $this->getOption(PublishingService::OPTIONS_FIELDS);
        $deliveryExcludedFieldsOptions = $this->hasOption(PublishingService::OPTIONS_EXCLUDED_FIELDS)
            ? $this->getOption(PublishingService::OPTIONS_EXCLUDED_FIELDS)
            : [];
        if (!$deliveryFieldsOptions) {
            $deliveryClass = new core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI);
            $deliveryProperties = tao_helpers_form_GenerisFormFactory::getClassProperties($deliveryClass);
            $defaultProperties = tao_helpers_form_GenerisFormFactory::getDefaultProperties();
            $deliveryProperties = array_merge($defaultProperties, $deliveryProperties);
            /** @var core_kernel_classes_Property $deliveryProperty */
            foreach ($deliveryProperties as $deliveryProperty) {
                if (!in_array($deliveryProperty->getUri(), $deliveryExcludedFieldsOptions)) {
                    $deliveryFieldsOptions[] = $deliveryProperty->getUri();
                }
            }
        }
        return $deliveryFieldsOptions;
    }
}
