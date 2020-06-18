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

namespace oat\taoPublishing\scripts\install;

use common_report_Report;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoPublishing\model\publishing\test\TestBackupService;

class RegisterPublishingFileSystem extends InstallAction
{
    public function __invoke($params): common_report_Report
    {
        /** @var FileSystemService $fileSystemService */
        $fileSystemService = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
        if (!$fileSystemService->hasDirectory(TestBackupService::FILESYSTEM_ID)) {
            $fileSystemService->createFileSystem(TestBackupService::FILESYSTEM_ID);
            $this->registerService(FileSystemService::SERVICE_ID, $fileSystemService);
        }

        return new common_report_Report(
            common_report_Report::TYPE_SUCCESS,
            'Publishing file system was successfully registered.'
        );
    }
}
