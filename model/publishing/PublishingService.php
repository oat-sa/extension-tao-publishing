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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
    const AUTH_TYPE = 'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformAuthType';

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
     * Send a $request to environment associated to the given $action
     *
     * @param $action
     * @param RequestInterface $request
     * @param array $clientOptions Http client options
     * @return ResponseInterface
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     * @throws \core_kernel_classes_EmptyProperty
     */
    public function callEnvironment($action, RequestInterface $request, array $clientOptions = [])
    {
        $environment = $this->findOneEnvironmentByAction($action);
        $boxId = $this->getEnvironmentBoxId($environment);

        $request = $request->withHeader('x-tao-box-id', $boxId->literal);

        return PlatformService::singleton()->callApi($environment->getUri(), $request, $clientOptions);
    }

    /**
     * Find one environment by action.
     *
     * @param $action
     * @return \core_kernel_classes_Resource
     * @throws \common_exception_NotFound
     */
    private function findOneEnvironmentByAction($action)
    {
        $environmentsFound = $this->findByAction($action);
        if (count($environmentsFound) === 0) {
            throw new \common_exception_NotFound('No environment found for action "' . $action . '".');
        }

        return $environmentsFound[0];
    }

    /**
     * @param $actionSearch
     * @return \core_kernel_classes_Resource|mixed|null
     * @throws \common_exception_NotFound
     */
    public function findByAction($actionSearch)
    {
        $environments = $this->getEnvironments();
        $environmentsFound = [];

        /** @var \core_kernel_classes_Resource $environment */
        foreach ($environments as $environment) {
            $actionProperties = $environment->getPropertyValues($this->getProperty(PublishingService::PUBLISH_ACTIONS));
            foreach ($actionProperties as $actionProperty) {
                if ($actionProperty) {
                    $actionProperty = preg_replace('/(\/|\\\\)+/', '\\', $actionProperty);
                    $action = preg_replace('/(\/|\\\\)+/', '\\', $actionSearch);
                    if ($actionProperty == $action) {
                        $environmentsFound[] = $environment;
                    }
                }
            }
        }

        return $environmentsFound;
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
                    $a = addslashes($item);
                    return str_replace('\\\\\\\\', '\\\\', addslashes($item));
                }, $values[PublishingService::PUBLISH_ACTIONS]);
            } else {
                $values[PublishingService::PUBLISH_ACTIONS] = str_replace('\\\\\\\\', '\\\\', addslashes($values[PublishingService::PUBLISH_ACTIONS]));
            }
        }
        return $values;
    }

    /**
     * Get environment box ID by action.
     *
     * @param $action
     * @return string
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     * @throws \core_kernel_classes_EmptyProperty
     */
    public function getBoxIdByAction($action)
    {
        $environment = $this->findOneEnvironmentByAction($action);

        return $this->getEnvironmentBoxId($environment)->literal;
    }

    /**
     * Set unique boc identifier for given environment
     *
     * @param \core_kernel_classes_Resource $environment
     * @return \core_kernel_classes_Container
     * @throws \common_Exception
     * @throws \core_kernel_classes_EmptyProperty
     */
    private function setBoxId(\core_kernel_classes_Resource $environment)
    {
        $boxId = uniqid();
        $boxIdProp = $this->getProperty(PlatformService::PROPERTY_SENDING_BOX_ID);
        $environment->setPropertyValue($this->getProperty(PlatformService::PROPERTY_SENDING_BOX_ID), $boxId);
        return $environment->getUniquePropertyValue($boxIdProp);
    }

    /**
     * Get box ID for environment. If does not exist create a new one.
     *
     * @param $environment
     * @return \core_kernel_classes_Container
     * @throws \common_Exception
     * @throws \core_kernel_classes_EmptyProperty
     */
    private function getEnvironmentBoxId($environment)
    {
        try {
            $boxId = $environment->getUniquePropertyValue($this->getProperty(PlatformService::PROPERTY_SENDING_BOX_ID));
        } catch (\core_kernel_classes_EmptyProperty $e) {
            $boxId = $this->setBoxId($environment);
        }
        return $boxId;
    }
}