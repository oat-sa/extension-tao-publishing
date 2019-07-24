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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA
 */

namespace oat\taoPublishing\scripts\tools;

use oat\oatbox\extension\script\ScriptAction;
use oat\taoPublishing\model\publishing\PublishingAuthService;
use oat\tao\model\auth\AbstractAuthService;
use oat\tao\model\session\restSessionFactory\RestSessionFactory;

/**
 * Class RegisterPublishingAuthTypeAction
 * @package oat\taoPublishing\scripts\tools
 * @usage sudo -u www-data php index.php 'oat\taoPublishing\scripts\tools\RegisterPublishingAuthTypeAction' -h
 */
class RegisterPublishingAuthTypeAction extends ScriptAction
{
    /**
     * @return array
     */
    protected function provideOptions()
    {
        return [
            'wetRun' => [
                'prefix' => 'w',
                'longPrefix' => 'wet-run',
                'flag' => true,
                'description' => 'Will perform real operations if it will be required',
                'required' => false
            ],
            'auth' => [
                'prefix' => 'a',
                'longPrefix' => 'authType',
                'flag' => false,
                'description' => 'Class name of Auth type',
                'required' => true
            ],
            'builder' => [
                'prefix' => 'b',
                'longPrefix' => 'authBuilder',
                'flag' => false,
                'description' => 'Class name of Auth builder',
                'required' => true
            ]
        ];
    }

    protected function provideDescription()
    {
        return 'Script for setting an auth type and auth builder for publishing';
    }

    protected function provideUsage()
    {
        return [
            'prefix'        => 'h',
            'longPrefix'    => 'help',
            'description'   => 'Shows this message'
        ];
    }

    public function run()
    {
        $isWetRun = $this->getOption('wetRun') !== null;
        $authType = ltrim($this->getOption('auth'), '\\');
        $authBuilder = ltrim($this->getOption('builder'), '\\');

        try {
            $authType = new $authType;
        } catch (\Exception $e) {
            return \common_report_Report::createFailure($e->getMessage());
        }

        /** @var PublishingAuthService $service */
        $service = $this->getServiceLocator()->get(PublishingAuthService::SERVICE_ID);
        $types = $service->getOption(AbstractAuthService::OPTION_TYPES);
        $alreadyRegistered = false;
        foreach ($types as $type) {
            if ($type instanceof $authType) {
                $alreadyRegistered = true;
                break;
            }
        }
        if (!$alreadyRegistered) {
            $types[] = new $authType();
            $service->setOption(AbstractAuthService::OPTION_TYPES, $types);
            if ($isWetRun) {
                $this->registerService(PublishingAuthService::SERVICE_ID, $service);
            }

        }

        /** @var RestSessionFactory $service */
        $service = $this->getServiceLocator()->get(RestSessionFactory::SERVICE_ID);
        $builders = $service->getOption(RestSessionFactory::OPTION_BUILDERS);
        if (!in_array($authBuilder, $builders)) {
            array_unshift($builders, $authBuilder);
            $service->setOption(RestSessionFactory::OPTION_BUILDERS, $builders);
            if ($isWetRun) {
                $this->registerService(RestSessionFactory::SERVICE_ID, $service);
            }

        }

        return \common_report_Report::createSuccess(get_class($authType) . ' successfully added.');
    }

}
