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
 * Copyright (c) 2018  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <olexander.zagovorychev@1pt.com>
 */

namespace oat\taoPublishing\scripts\update\v0_6_0;


use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\tao\model\auth\BasicAuth;
use oat\taoPublishing\model\PlatformService;

/**
 * split deprecated Remote environment auth field to login and password
 *
 * Class UpdateAuthFieldAction
 * @package oat\taoPublishing\scripts\update\v0_6_0
 */
class UpdateAuthFieldAction extends AbstractAction
{
    use OntologyAwareTrait;

    public function __invoke($params)
    {
        $report = \common_report_Report::createInfo('Moving Platforms to the new authenticator');

        // move all existing platforms to the new format
        $platforms = $this->getClass(PlatformService::CLASS_URI)->searchInstances();

        $authTypeProp = $this->getProperty(PlatformService::PROPERTY_AUTH_TYPE);
        $deprecatedProp = $this->getProperty('http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformAuth');

        $basicLoginProp = $this->getProperty(BasicAuth::PROPERTY_LOGIN);
        $basicPasswordProp = $this->getProperty(BasicAuth::PROPERTY_PASSWORD);

        /** @var \core_kernel_classes_Resource $platform */
        foreach ($platforms as $platform) {
            $movingReport = \common_report_Report::createInfo('Moving platform: ' . $platform->getLabel());
            $type = $platform->getOnePropertyValue($authTypeProp);
            if ($type) {
                $movingReport->add(\common_report_Report::createInfo('Platform: ' . $platform->getLabel() . ' has been already moved'));
            } else {
                $platform->editPropertyValues($authTypeProp, BasicAuth::CLASS_BASIC_AUTH);
                $authData = $platform->getOnePropertyValue($deprecatedProp);
                $password = $login = '';
                if ($authData && mb_strpos($authData, ':') !== false) {
                    list($login, $password) = explode(':', $authData);
                }
                $platform->removePropertyValues($deprecatedProp);
                $platform->editPropertyValues($basicLoginProp, $login);
                $platform->editPropertyValues($basicPasswordProp, $password);
                $movingReport->add(\common_report_Report::createSuccess('Platform "'.$platform->getLabel().'" moved'));
            }

            $report->add($movingReport);
         }

         return $report;
    }
}
