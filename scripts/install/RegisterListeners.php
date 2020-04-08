<?php declare(strict_types=1);

namespace oat\taoPublishing\scripts\install;

use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoPublishing\model\publishing\delivery\listeners\DeliveryTestSaverListener;
use oat\taoPublishing\model\publishing\delivery\DeliveryTestService;

class RegisterListeners extends InstallAction
{
    /**
     * @param mixed $params
     *
     * @return \common_report_Report
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);

        /** @noinspection PhpParamsInspection */
        $eventManager->attach(
            DeliveryCreatedEvent::class,
            new DeliveryTestSaverListener()
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Publisher delivery events registered');
    }
}