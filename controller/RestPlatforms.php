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

namespace oat\taoPublishing\controller;

use oat\taoPublishing\model\platform\Platform;
use oat\taoPublishing\model\PlatformService;
use tao_actions_RestController;

/**
 * @OA\Info(title="Platform API", version="0.1")
 */
class RestPlatforms extends tao_actions_RestController
{
    /**
     * @OA\Get(
     *     path="/taoPublishing/api/platforms",
     *     tags={"platforms"},
     *     summary="Index of platforms",
     *     description="Index of platforms",
     *     @OA\Response(
     *         response="200",
     *         description="Platform data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="boolean",
     *                     description="`false` on failure, `true` on success",
     *                 ),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         ref="#/components/schemas/Platform",
     *                     ),
     *                 ),
     *                 example= {
     *                     "success": true,
     *                     "data": {
     *                         {
     *                             "uri": "http://sample/first.rdf#i1536680377163170",
     *                             "label": "Sample label",
     *                             "rootUri": "http://ROOT/URI",
     *                             "boxId": "1",
     *                             "isEnabled": true,
     *                         }
     *                     }
     *                 }
     *             )
     *         ),
     *     ),
     * )
     */
    public function get()
    {
        try {
            if ($this->getRequestMethod() !== \Request::HTTP_GET) {
                throw new \common_exception_BadRequest();
            }

            $list = [];
            foreach ($this->getClass(PlatformService::CLASS_URI)->getInstances(true) as $platformResource) {
                $list[] = new Platform($platformResource);
            }

            $this->returnJson([
                'success' => true,
                'data' => $list,
            ]);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }
}
