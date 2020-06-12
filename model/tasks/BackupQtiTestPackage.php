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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */
declare(strict_types=1);

namespace oat\taoPublishing\model\tasks;

use Exception;
use JsonSerializable;
use common_exception_MissingParameter;
use common_report_Report;
use oat\oatbox\extension\AbstractAction;
use oat\tao\model\taskQueue\Task\TaskAwareInterface;
use oat\tao\model\taskQueue\Task\TaskAwareTrait;
use oat\taoPublishing\model\publishing\test\TestBackupService;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class BackupQtiTestPackage extends AbstractAction implements JsonSerializable, TaskAwareInterface
{
    use TaskAwareTrait;
    use ServiceLocatorAwareTrait;

    public const PARAM_DELIVERY_URI = 'deliveryUri';

    public function __invoke($params)
    {
        if (!isset($params[self::PARAM_DELIVERY_URI])) {
            throw new common_exception_MissingParameter('Missing required parameter: ' . self::PARAM_DELIVERY_URI);
        }

        $deliveryUri = $params[self::PARAM_DELIVERY_URI];
        $report = common_report_Report::createInfo("Start origin QTI test package export for delivery {$deliveryUri}");

        try {
            /** @var TestBackupService $testBackupService */
            $testBackupService = $this->getServiceLocator()->get(TestBackupService::class);
            $file = $testBackupService->backupDeliveryTestPackage($deliveryUri);

            $report->add(
                common_report_Report::createSuccess(sprintf('QTI test was exported to %s', $file->getPrefix()))
            );
        } catch (Exception $e) {
            $this->logError($e->getMessage());
            $report->add(common_report_Report::createFailure(sprintf('QTI Test backup failed for delivery: %s', $deliveryUri)));
        }

        return $report;
    }

    public function jsonSerialize()
    {
        return __CLASS__;
    }
}
