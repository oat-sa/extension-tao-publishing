<?php
/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 *
 */

declare(strict_types=1);

namespace oat\taoPublishing\model;

use common_exception_InvalidArgumentType;
use common_exception_PreConditionFailure;
use common_Utils;
use oat\taoPublishing\model\entity\Platform;
use oat\taoPublishing\model\PlatformService;
use tao_models_classes_CrudService;

class CrudPlatformsService extends tao_models_classes_CrudService
{
    /**
     * @return PlatformService
     */
    protected function getClassService()
    {
        return $this->getServiceLocator()->get(PlatformService::class);
    }

    public function get($uri)
    {
        if (!common_Utils::isUri($uri)) {
            throw new common_exception_InvalidArgumentType();
        }
        if (!($this->isInScope($uri))) {
            throw new common_exception_PreConditionFailure("The URI must be a valid resource under the root Class");
        }
        $resource = $this->getResource($uri);

        return new Platform($resource);
    }

    public function getAll()
    {
        $list = [];
        foreach ($this->getRootClass()->getInstances(true) as $resource) {
            $list[] = new Platform($resource);
        }

        return $list;
    }
}
