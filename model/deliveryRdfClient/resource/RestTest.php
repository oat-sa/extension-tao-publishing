<?php declare(strict_types=1);
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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoPublishing\model\deliveryRdfClient\resource;

use oat\taoPublishing\model\deliveryRdfClient\entity\CompileDeferredResult;
use oat\taoPublishing\model\deliveryRdfClient\entity\Delivery;
use oat\taoPublishing\model\deliveryRdfClient\entity\TestPackage;
use oat\taoPublishing\model\deliveryRdfClient\resource\restTest\CompileDeferredAction;
use oat\taoPublishing\model\deliveryRdfClient\resource\restTest\exception\CompileDeferredFailureException;

class RestTest
{

    /**
     * @var CompileDeferredAction
     */
    private $compileDeferredAction;

    public function __construct(
        CompileDeferredAction $action
    ) {
        $this->compileDeferredAction = $action;
    }

    /**
     * @param TestPackage $testPackage
     * @param Delivery $delivery
     * @param string $importerId
     * @return CompileDeferredResult
     *
     * @throws CompileDeferredFailureException
     */
    public function compileDeferred(
        TestPackage $testPackage,
        Delivery $delivery,
        string $importerId
    ): CompileDeferredResult {
        return $this->compileDeferredAction->compileDeferred($testPackage, $delivery, $importerId);
    }
}