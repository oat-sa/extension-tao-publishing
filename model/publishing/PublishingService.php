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

namespace oat\taoPublishing\model\publishing;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\log\LoggerAwareTrait;
use oat\taoPublishing\model\PlatformService;

/**
 * Class PublishingService
 * @package oat\taoPublishing\model\publishing
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 */
class PublishingService extends ConfigurableService
{
    use LoggerAwareTrait;
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoPublishing/PublishingService';
    const PUBLISH_ACTIONS = 'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformPublishAction';

    const OPTIONS_ACTIONS = 'actions';
    const OPTIONS_FIELDS = 'fields';
    const OPTIONS_EXCLUDED_FIELDS = 'excluded_fields';

    /**
     * @return array
     */
    public function getEnvironments()
    {
        $publishingClass = PlatformService::singleton()->getRootClass();
        $environments = $publishingClass->getInstances(true);
        return $environments;
    }

    /**
     * @return array
     */
    public function getPublishingActions()
    {
        $actions = $this->getOption(self::OPTIONS_ACTIONS);
        $options = [];
        foreach ($actions as $action) {
            $options[] = [
                'data' => (new \ReflectionClass($action))->getShortName(),
                'parent' => 0,
                'attributes' => [
                    'id' => $action,
                    'class' => 'node-instance'
                ]
            ];
        }
        return $options;
    }

    /**
     * @param $values
     * @return array
     */
    public function addSlashes($values)
    {
        if (isset($values[PublishingService::PUBLISH_ACTIONS])) {
            if (is_array($values[PublishingService::PUBLISH_ACTIONS])) {
                $values[PublishingService::PUBLISH_ACTIONS] = array_map(function($item) {
                    return addslashes($item);
                }, $values[PublishingService::PUBLISH_ACTIONS]);
            } else {
                $values[PublishingService::PUBLISH_ACTIONS] = addslashes($values[PublishingService::PUBLISH_ACTIONS]);
            }
        }
        return $values;
    }
}