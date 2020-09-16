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

namespace oat\taoPublishing\model\publishing\delivery;

use core_kernel_classes_Class as CoreClass;
use common_exception_InvalidArgumentType;
use core_kernel_classes_Resource;
use core_kernel_persistence_Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use League\Flysystem\FileNotFoundException;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\controller\RestTest;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\exception\PublishingFailedException;
use oat\taoPublishing\model\publishing\test\TestBackupService;
use Psr\Http\Message\ResponseInterface;

class RemoteDeliveryPublisher extends ConfigurableService
{
    use OntologyAwareTrait;
    use LoggerAwareTrait;

    /** @var core_kernel_classes_Resource */
    private $test;

    /** @var core_kernel_classes_Resource */
    private $delivery;

    /** @var core_kernel_classes_Resource */
    private $environment;

    public function publish(
        core_kernel_classes_Resource $delivery,
        core_kernel_classes_Resource $environment,
        core_kernel_classes_Resource $test
    ): string {
        $this->delivery = $delivery;
        $this->environment = $environment;
        $this->test = $test;

        $this->validateResources();

        $requestData = $this->prepareRequestData();
        $response = $this->callRemotePublishingApi($requestData);

        return $this->processApiResponse($response);
    }

    /**
     * @param CoreClass $deliveryClass
     *
     * @return array
     */
    protected function getParentLabels(CoreClass $deliveryClass): array
    {
        $labels = [];

        foreach ($deliveryClass->getParentClasses(true) as $parentClass) {
            if ($parentClass->getUri() === DeliveryAssemblyService::CLASS_URI) {
                break;
            }

            $labels[] = $parentClass->getLabel();
        }

        return array_reverse($labels);
    }

    private function validateResources(): void
    {
        if (!$this->delivery->exists()) {
            $message = sprintf(__('Delivery with URI "%s" does not exist.'), $this->delivery->getUri());
            throw new PublishingFailedException($message);
        }

        if (!$this->environment->exists()) {
            $message = sprintf(__('Remote environment with URI "%s" does not exist.'), $this->delivery->getUri());
            throw new PublishingFailedException($message);
        }
    }

    /**
     * @throws PublishingFailedException
     * @throws common_exception_InvalidArgumentType
     * @throws core_kernel_persistence_Exception
     *
     * @return array
     */
    private function prepareRequestData(): array
    {
        try {
            $qtiPackageFile = $this->getQtiTestPackageFile($this->delivery);
            $requestData = [
                [
                    'name' => RestTest::REST_FILE_NAME,
                    'filename' => RestTest::REST_FILE_NAME,
                    'contents' => $qtiPackageFile->readPsrStream(),
                ],
                [
                    'name' => RestTest::REST_IMPORTER_ID,
                    'contents' => 'taoQtiTest',
                ],
                [
                    'name' => RestTest::REST_DELIVERY_PARAMS,
                    'contents' => json_encode($this->getSynchronizedDeliveryProperties($this->delivery)),
                ]
            ];

            $deliveryClass = current($this->delivery->getTypes());

            if ($deliveryClass->getUri() !== DeliveryAssemblyService::CLASS_URI) {
                $labels = $this->getParentLabels($deliveryClass);

                $labels[] = $deliveryClass->getLabel();

                $requestData[] = [
                    'name' => RestTest::REST_DELIVERY_CLASS_LABELS,
                    'contents' => json_encode($labels),
                ];
            }

            return $requestData;
        } catch (FileNotFoundException $e) {
            $this->logError($e->getMessage(), [$e->__toString()]);

            throw new PublishingFailedException(sprintf(
                __('QTI Test backup file not found for delivery "%s"'),
                $this->delivery->getLabel()
            ));
        }
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @return File
     * @throws core_kernel_persistence_Exception
     */
    private function getQtiTestPackageFile(core_kernel_classes_Resource $delivery): File
    {
        $packagePath = $delivery->getOnePropertyValue($this->getProperty(TestBackupService::PROPERTY_QTI_TEST_BACKUP_PATH));
        $packagePath = (string)  $packagePath;

        return $this->getServiceLocator()
            ->get(FileSystemService::SERVICE_ID)
            ->getDirectory(TestBackupService::FILESYSTEM_ID)
            ->getFile($packagePath);
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @return array
     * @throws common_exception_InvalidArgumentType
     */
    private function getSynchronizedDeliveryProperties(core_kernel_classes_Resource $delivery): array
    {
        $propertyList = [];
        $propertyValues = $delivery->getPropertiesValues($this->getPublishingDeliveryService()->getSyncFields());
        foreach ($propertyValues as $propertyKey => $values) {
            $value = reset($values);
            if ($value instanceof core_kernel_classes_Resource) {
                $value = $value->getUri();
            }
            $propertyList[$propertyKey] = (string) $value;
        }
        $propertyList[PublishingDeliveryService::ORIGIN_DELIVERY_ID_FIELD] = $delivery->getUri();

        return $propertyList;
    }

    /**
     * @param array $requestData
     * @return ResponseInterface
     */
    private function callRemotePublishingApi(array $requestData): ResponseInterface
    {
        try {
            $body = new MultipartStream($requestData);
            $request = new Request('POST', '/taoDeliveryRdf/RestTest/compileDeferred');
            $request = $request->withBody($body);

            return $this->getPlatformService()->callApi($this->environment->getUri(), $request);
        } catch (ConnectException $e) {
            $this->logError($e->getMessage(), [$e->__toString()]);

            $message = __('Remote environment "%s" is not reachable.', $this->environment->getLabel());
            throw new PublishingFailedException($message);
        } catch (ClientException $e) {
            $this->logError($e->getMessage(), [$e->__toString()]);

            $response = $e->getResponse();
            $message = __('Bad request.');
            if ($response->getStatusCode() === 401) {
                $responseBody = json_decode($response->getBody()->getContents(), true);
                $message = $responseBody['errorMsg'] ?? $response->getReasonPhrase();
            }

            throw new PublishingFailedException($message, $e->getCode());
        }
    }

    private function getPlatformService(): PlatformService
    {
        return $this->getServiceLocator()->get(PlatformService::class);
    }

    private function getPublishingDeliveryService(): PublishingDeliveryService
    {
        return $this->getServiceLocator()->get(PublishingDeliveryService::SERVICE_ID);
    }

    /**
     * @param ResponseInterface $response
     */
    private function processApiResponse(ResponseInterface $response): string
    {
        if ($response->getStatusCode() !== 200) {
            throw new PublishingFailedException(__('Request failed with message: %s', $response->getBody()->getContents()));
        }

        $responseData = json_decode($response->getBody()->getContents(), true);
        if (!isset($responseData['success'], $responseData['data']['reference_id']) || $responseData['success'] !== true) {
            throw new PublishingFailedException(__('API request execution failed on remote server.'));
        }

        return $responseData['data']['reference_id'];
    }
}
