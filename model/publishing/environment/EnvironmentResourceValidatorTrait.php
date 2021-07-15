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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoPublishing\model\publishing\environment;

use core_kernel_classes_Resource;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\exception\PublishingInvalidArgumentException;

trait EnvironmentResourceValidatorTrait
{
    private function validateEnvironment(core_kernel_classes_Resource $environment)
    {
        if (!$environment->exists()) {
            throw new PublishingInvalidArgumentException(
                __(sprintf('Remote environment with URI "%s" does not exist.', $environment->getUri()))
            );
        }

        $publishingEnabled = (bool) $environment->getOnePropertyValue(
            $this->getProperty(
                PlatformService::PROPERTY_IS_PUBLISHING_ENABLED
            )
        );

        if ($publishingEnabled === false) {
            throw new PublishingInvalidArgumentException(
                __(sprintf('Remote publishing is disabled for environment with URI "%s"', $environment->getUri()))
            );
        }
    }
}
