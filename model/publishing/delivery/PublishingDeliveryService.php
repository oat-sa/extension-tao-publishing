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

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\log\LoggerAwareTrait;

/**
 * Class PublishingDeliveryService
 * @package oat\taoPublishing\model\publishing
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 */
class PublishingDeliveryService extends ConfigurableService
{
    use LoggerAwareTrait;
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoPublishing/PublishingDeliveryService';
    const ORIGIN_DELIVERY_ID_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#OriginDeliveryID';
    const ORIGIN_TEST_ID_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#OriginTestID';
    const DELIVERY_REMOTE_SYNC_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#RemoteSync';
    const DELIVERY_REMOTE_SYNC_COMPILE_ENABLED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled';

    const DELIVERY_REMOTE_SYNC_REST_OPTION = 'remote-publish';
}