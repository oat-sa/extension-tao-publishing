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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoPublishing\controller;

use common_ext_ExtensionsManager;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\BasicAuth;
use oat\tao\model\oauth\DataStore;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\PublishingService;

/**
 * Sample controller
 *
 * @author Open Assessment Technologies SA
 * @package taoPublishing
 * @license GPL-2.0
 *
 */
class PlatformAdmin extends \tao_actions_SaSModule
{

    use OntologyAwareTrait;

    public function __construct()
    {
        parent::__construct();
        $this->service = $this->getClassService();
    }

    /**
     * @param $instance
     * @throws \Exception
     * @throws \tao_models_classes_dataBinding_GenerisFormDataBindingException
     */
    public function saveInstance($instance = null)
    {
        if(!\tao_helpers_Request::isAjax()){
            throw new \Exception("wrong request mode");
        }

        /** @var PublishingService $publishingService */
        $publishingService = $this->getServiceLocator()->get(PublishingService::SERVICE_ID);
        $clazz = $this->getCurrentClass();
        $myFormContainer = new \tao_actions_form_Instance($clazz, $instance);

        $myForm = $myFormContainer->getForm();

        if($myForm->isSubmited()){
            if($myForm->isValid()){

                // For treeBox component we need to add slashes for rdf value before saving
                $values = $publishingService->addSlashes($myForm->getValues());

                // according to the auth type we need to add properties for the authenticator
                $authType = $this->getRequest()->getParameter('taoPlatformAuthType');
                if ($authType == BasicAuth::CLASS_BASIC_AUTH) {
                    $values[PlatformService::PROPERTY_AUTH_TYPE] = $authType;
                    $values[BasicAuth::LOGIN] = $this->getRequest()->getParameter('login');
                    $values[BasicAuth::PASSWORD] = $this->getRequest()->getParameter('password');
                }

                $message = __('Undefined Instance can not be saved');
                if (!$instance) {
                    $this->createInstance(array($clazz), $values);
                    $message = __('Instance created');
                } elseif ($instance instanceof \core_kernel_classes_Resource) {
                    // save properties
                    $binder = new \tao_models_classes_dataBinding_GenerisFormDataBinder($instance);
                    $binder->bind($values);
                    $message = __('Instance saved');
                }

                $this->setData('message', $message);
                $this->setData('reload', true);
            }
        }

        $actionsElementClass = \tao_helpers_Uri::encode(PublishingService::PUBLISH_ACTIONS);
        $actionsElement = $myForm->getElement($actionsElementClass);
        $actionsElement->setOptions($publishingService->getPublishingActions());
        $myForm->removeElement($actionsElement);
        $myForm->addElement($actionsElement);

        $this->setData('myForm', $myForm->render());
        $this->setView('form.tpl', 'tao');
    }

    public function editInstance()
    {
        $this->setData('formTitle', __('Edit Instance'));
        $this->saveInstance($this->getCurrentInstance());
    }

    public function addInstanceForm()
    {
        $this->setData('formTitle', __('Create instance'));
        $this->saveInstance();
    }

    /**
     * @throws \core_kernel_persistence_Exception
     * @throws \tao_models_classes_MissingRequestParameterException
     */
    public function authConfiguration()
    {
        $instance = $this->getCurrentInstance();
        $authType = $instance->getOnePropertyValue($this->getProperty(PlatformService::PROPERTY_AUTH_TYPE));

        /** @var common_ext_ExtensionsManager $extensionManagerService */
        $extensionManagerService = $this->getServiceLocator()->get(common_ext_ExtensionsManager::SERVICE_ID);
        $authClass = $this->getClass(BasicAuth::CLASS_BASIC_AUTH);

        $credentials = [
            'type' => BasicAuth::CLASS_BASIC_AUTH,
            'allowedTypes' => [BasicAuth::CLASS_BASIC_AUTH => [
                'label' => $authClass->getLabel(),
                'selected' => false,
            ]],
            'login' => '',
            'password' => '',
        ];

        if ($authType) {
            switch ($authType->getUri()) {
                case BasicAuth::CLASS_BASIC_AUTH:
                    $credentials['login'] = $instance->getOnePropertyValue($this->getProperty(BasicAuth::LOGIN))->literal;
                    $credentials['password'] = $instance->getOnePropertyValue($this->getProperty(BasicAuth::PASSWORD))->literal;
                    $credentials['allowedTypes'][BasicAuth::CLASS_BASIC_AUTH]['selected'] = true;
                    break;
                case 'oauth2':
                    break;
            }
        }

        // oAuth 1.0 // needs to be changed to taoOauth 2.0
        if ($extensionManagerService->isInstalled('taoOauth')) {
            $oAuthClass = $this->getClass(DataStore::CLASS_URI_OAUTH_CONSUMER);
            $credentials['allowedTypes'] = array_merge($credentials['allowedTypes'],
                [DataStore::CLASS_URI_OAUTH_CONSUMER => ['label' => $oAuthClass->getLabel(), 'selected' => false]]);
        }

        $this->returnJson([
            'data' => $credentials,
            'success' => true,
        ]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see tao_actions_RdfController::getClassService()
     */
    public function getClassService()
    {
        return PlatformService::singleton();
    }
}