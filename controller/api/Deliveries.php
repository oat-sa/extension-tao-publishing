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

namespace oat\taoPublishing\controller\api;

use common_exception_BadRequest;
use common_exception_MissingParameter;
use common_exception_NotImplemented;
use common_exception_RestApi;
use Exception;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\taoPublishing\model\publishing\delivery\RemotePublishingService;
use oat\taoPublishing\model\publishing\exception\PublishingInvalidArgumentException;

class Deliveries extends \tao_actions_RestController
{
    use TaskLogActionTrait;

    public const REST_DELIVERY_URI = 'delivery-uri';
    public const REST_REMOTE_ENVIRONMENTS = 'remote-environments';

    public function index()
    {
        return $this->returnFailure(new common_exception_BadRequest($this->getPsrRequest()->getUri()->getPath()));
    }

    /**
     * @OA\Post(
     *     path="/taoPublishing/api/deliveries/publish",
     *     tags={"deliveries"},
     *     summary="Publish delivery to remote environment",
     *     description="Publish delivery to remote environment",
     *     @OA\Response(
     *         response="200",
     *         description="Delivery publishing successful",
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
     *                         ref="#/components/schemas/TaskLog",
     *                     ),
     *                 ),
     *                 @OA\Property(
     *                     property="version",
     *                     type="string",
     *                 ),
     *                 example= {
     *                     "success": true,
     *                     "data": {
     *                         {
     *                             "id": "http://sample/first.rdf#i1111111111111111",
     *                             "status": "Finished",
     *                             "report": {
     *                                 {
     *                                     "type": "info",
     *                                     "message": "Running task http://sample/first.rdf#i1111111111111111"
     *                                 }
     *                             }
     *                         }
     *                     },
     *                     "version": "3.4.0-sprint131"
     *                 }
     *             )
     *         ),
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Delivery publishing bad request",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="boolean",
     *                     description="`false` on failure, `true` on success",
     *                 ),
     *                 @OA\Property(
     *                     property="errorCode",
     *                     type="string",
     *                     description="Exception error code",
     *                 ),
     *                 @OA\Property(
     *                     property="errorMsg",
     *                     type="string",
     *                     description="Exception message",
     *                 ),
     *                 @OA\Property(
     *                     property="version",
     *                     type="string",
     *                 ),
     *                 example= {
     *                     "success": false,
     *                     "errorCode": 0,
     *                     "errorMsg": "Delivery resource with URI 'http://BAD/URI.rdf#i1' does not exist.",
     *                     "version": "3.4.0-sprint131"
     *                 }
     *             )
     *         ),
     *     ),
     * )
     * @OA\Schema(
     *     schema="TaskLog",
     *     type="object",
     *     @OA\Property(
     *         property="id",
     *         type="string",
     *         description="ID"
     *     ),
     *     @OA\Property(
     *         property="status",
     *         type="string",
     *         description="Status"
     *     ),
     *     @OA\Property(
     *         property="report",
     *         type="array",
     *         @OA\Items(
     *             ref="#/components/schemas/TaskReport",
     *         )
     *     ),
     * )
     * @OA\Schema(
     *     schema="TaskReport",
     *     type="object",
     *     @OA\Property(
     *         property="type",
     *         type="string",
     *         description="Type"
     *     ),
     *     @OA\Property(
     *         property="message",
     *         type="string",
     *         description="Message"
     *     ),
     * )
     */
    public function publish()
    {
        try {
            if ($this->getRequestMethod() !== \Request::HTTP_POST) {
                throw new common_exception_NotImplemented('Only POST method is accepted to publish deliveries');
            }
            if (!$this->hasRequestParameter(self::REST_DELIVERY_URI)) {
                throw new common_exception_MissingParameter(self::REST_DELIVERY_URI, $this->getRequestURI());
            }
            if (!$this->hasRequestParameter(self::REST_REMOTE_ENVIRONMENTS)) {
                throw new common_exception_MissingParameter(self::REST_REMOTE_ENVIRONMENTS, $this->getRequestURI());
            }
            $deliveryUri = $this->getRequestParameter(self::REST_DELIVERY_URI);
            $remoteEnvironmentUris = $this->getRequestParameter(self::REST_REMOTE_ENVIRONMENTS);
            if (!is_array($remoteEnvironmentUris)) {
                $remoteEnvironmentUris = [$remoteEnvironmentUris];
            }

            /** @var RemotePublishingService $remotePublishingService */
            $remotePublishingService = $this->getServiceLocator()->get(RemotePublishingService::class);
            $tasks = $remotePublishingService->publishDeliveryToEnvironments($deliveryUri, $remoteEnvironmentUris);

            $response = [];
            foreach ($tasks as $task) {
                $response[] = $this->getTaskLogReturnData($task->getId());
            }

            return $this->returnSuccess($response);
        } catch (PublishingInvalidArgumentException $e) {
            $this->returnFailure(new common_exception_RestApi($e->getUserMessage()));
        } catch (Exception $e) {
            $this->returnFailure($e);
        }
    }
}
