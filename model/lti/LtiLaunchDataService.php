<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA
 */
namespace oat\taoPublishing\model\lti;

use oat\ltiDeliveryProvider\model\LtiLaunchDataService as OriginLtiLaunchDataService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;

/**
 * Class LtiLaunchDataService
 * @package oat\taoPublishing\model\lti
 */
class LtiLaunchDataService extends OriginLtiLaunchDataService
{
    /**
     * @param \taoLti_models_classes_LtiLaunchData $launchData
     * @return \core_kernel_classes_Resource
     */
    public function findDeliveryFromLaunchData(\taoLti_models_classes_LtiLaunchData $launchData)
    {
        $delivery = parent::findDeliveryFromLaunchData($launchData);
        if (!$delivery->exists()) {
            $deliveryClass = DeliveryAssemblyService::singleton()->getRootClass();
            $deliveryInstances = $deliveryClass->searchInstances([PublishingDeliveryService::ORIGIN_DELIVERY_ID_FIELD => $delivery->getUri()], ['like' => false, 'recursive' => true]);
            if ($deliveryInstances) {
                $delivery = current($deliveryInstances);
            }
        }

        return $delivery;
    }
}
