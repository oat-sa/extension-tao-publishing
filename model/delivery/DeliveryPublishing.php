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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoPublishing\model\delivery;

use oat\generis\model\OntologyAwareTrait;
use oat\taoDeliveryRdf\model\DeliveryPublishing as OriginDeliveryPublishing;
use core_kernel_classes_Resource;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;

/**
 * Services to manage Deliveries
 *
 * @access public
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 * @package taoPublishing
 */
class DeliveryPublishing extends OriginDeliveryPublishing
{
    use OntologyAwareTrait;

    const OPTION_REMOTE_PUBLISH_OPTION = 'remote-publish';

    public function checkRequestParameters(\Request $request, core_kernel_classes_Resource $delivery)
    {
        if ($request->hasParameter(self::OPTION_REMOTE_PUBLISH_OPTION)) {
            $property = $this->getProperty(PublishingDeliveryService::DELIVERY_REMOTE_SYNC_FIELD);
            $delivery->setPropertyValue($property, PublishingDeliveryService::DELIVERY_REMOTE_SYNC_COMPILE_ENABLED);
        }
        return $delivery;
    }
}
